<?php

/* Copyright (c) 2022 Leifos GmbH, GPLv3*/

/**
 * @author Alexander Killing <killing@leifos.de>
 */
class ilLfCleanCOPageHistoryCronjob extends ilCronJob
{
    const DEFAULT_SCHEDULE_TIME = 1;

    /**
     * @var \ilLfCleanCOPageHistoryPlugin
     */
    protected $plugin;

    /**
     * @return string
     */
    public function getId()
    {
        return ilLfCleanCOPageHistoryPlugin::getInstance()->getId();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return ilLfCleanCOPageHistoryPlugin::getInstance()->txt('cron_job');;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return ilLfCleanCOPageHistoryPlugin::getInstance()->txt('cron_job_info');
    }

    /**
     * @return int
     */
    public function getDefaultScheduleType()
    {
        return self::SCHEDULE_TYPE_IN_HOURS;
    }

    /**
     * @return array|int
     */
    public function getDefaultScheduleValue()
    {
        return self::DEFAULT_SCHEDULE_TIME;
    }

    /**
     * @return bool
     */
    public function hasAutoActivation()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasFlexibleSchedule()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasCustomSettings()
    {
        return true;
    }

    /**
     * @return \ilCronJobResult
     */
    public function run()
    {
        global $DIC;

        $settings = $DIC->settings();
        $settings->get("");

        $log = ilLoggerFactory::getLogger("copg");
        $log->debug("----- Delete old page history entries, Start -----");

        $result = new ilCronJobResult();

        $plugin = $this->getPlugin();

        $x_days = (int) $settings->get("copg_cron_days");
        $keep_entries = (int) $settings->get("copg_cron_keep_entries");
        $log->debug("... $x_days days, keep $keep_entries");

        foreach ($this->getMaxHistEntryPerPageOlderThanX($x_days) as $page) {
            $max_deletable = $this->getMaxDeletableNr($keep_entries, $page["parent_type"], $page["page_id"], $page["lang"]);
            $delete_lower_than_nr = min($page["max_nr"], $max_deletable);
            if ($delete_lower_than_nr > 0) {
                $this->deleteHistoryEntriesOlderEqualThanNr(
                    $delete_lower_than_nr,
                    $page["parent_type"],
                    $page["page_id"],
                    $page["lang"]
                );
            }
        }

        // foreach

        $log->debug("----- Delete old page history entries, End -----");
        return $result;
    }

    protected function getMaxHistEntryPerPageOlderThanX(int $xdays) : Iterator
    {
        global $DIC;

        $hdate = new ilDateTime(date("Y-m-d H:i:s"), IL_CAL_DATETIME);
        $hdate->increment(ilDateTime::DAY, (-1 * $xdays));

        $db = $DIC->database();
        $set = $db->queryF("SELECT MAX(nr) max_nr, parent_type, page_id, lang FROM page_history " .
            " WHERE nr > %s AND hdate < %s GROUP BY parent_type, page_id, lang ",
            ["integer", "timestamp"],
            [0, $hdate]
        );
        while ($rec = $db->fetchAssoc($set)) {
            yield [
                "parent_type" => $rec["parent_type"],
                "page_id" => $rec["page_id"],
                "lang" => $rec["lang"],
                "max_nr" => (int) $rec["max_nr"]
            ];
        }
    }

    protected function getMaxDeletableNr(
        int $keep_entries,
        string $parent_type,
        int $page_id,
        string $lang) : int
    {
        global $DIC;
        $db = $DIC->database();
        $set = $db->queryF("SELECT MAX(nr) mnr FROM page_history " .
            " WHERE parent_type = %s AND page_id = %s AND lang = %s ",
            ["text", "integer", "text"],
            [$parent_type, $page_id, $lang]
        );
        $max_old_nr = 0;
        if ($rec = $db->fetchAssoc($set)) {
            $max_old_nr = (int) $rec["mnr"];
        }
        $max_old_nr -= $keep_entries;
        if ($max_old_nr < 0) {
            $max_old_nr = 0;
        }
        return $max_old_nr;
    }


    protected function deleteHistoryEntriesOlderEqualThanNr(
        int $delete_lower_than_nr,
        string $parent_type,
        int $page_id,
        string $lang
    )
    {
        /** @var \ILIAS\DI\Container $DIC */
        global $DIC;

        $db = $DIC->database();
        $log = ilLoggerFactory::getLogger("copg");

        // PC Media
        $this->getPlugin()->includeClass("class.LfPCMediaObjectMods.php");
        LfPCMediaObjectMods::_deleteHistoryLowerEqualThan(
            $parent_type,
            $page_id,
            $lang,
            $delete_lower_than_nr
        );

        // PC Skills
        $this->getPlugin()->includeClass("class.LfPageContentUsageMods.php");
        $this->getPlugin()->includeClass("class.LfPCSkillsMods.php");
        LfPCSkillsMods::_deleteHistoryLowerEqualThan(
            $parent_type,
            $page_id,
            $lang,
            $delete_lower_than_nr
        );

        // PC Content Includes
        $this->getPlugin()->includeClass("class.LfPCContentIncludeMods.php");
        LfPCContentIncludeMods::_deleteHistoryLowerEqualThan(
            $parent_type,
            $page_id,
            $lang,
            $delete_lower_than_nr
        );

        // PC File Lists
        $this->getPlugin()->includeClass("class.LfPCFileListMods.php");
        LfPCFileListMods::_deleteHistoryLowerEqualThan(
            $parent_type,
            $page_id,
            $lang,
            $delete_lower_than_nr
        );

        // main entries in history
        $q = "DELETE FROM page_history " .
            " WHERE parent_type = " . $db->quote($parent_type, "text") .
            " AND page_id = " . $db->quote($page_id, "integer") .
            " AND lang = " . $db->quote($lang, "text") .
            " AND nr <= " . $db->quote($delete_lower_than_nr, "integer");

        $log->debug($q);
        $db->manipulate($q);
    }

    /**
     * @return \ilLfCleanCOPageHistoryPlugin
     */
    public function getPlugin()
    {
        return \ilLfCleanCOPageHistoryPlugin::getInstance();
    }

    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form)
    {
        global $DIC;
        $lng = $DIC['lng'];

        $lng->loadLanguageModule("copg");

        $settings = $DIC->settings();

        $ti = new ilNumberInputGUI(
            $this->getPlugin()->txt("copg_cron_days"),
            "copg_cron_days"
        );
        $ti->setSize(6);
        $ti->setSuffix($this->getPlugin()->txt("copg_days"));
        $ti->setInfo($this->getPlugin()->txt("copg_cron_days_info"));
        $ti->setValue($settings->get("copg_cron_days"));
        $a_form->addItem($ti);

        $ti = new ilNumberInputGUI($this->getPlugin()->txt("copg_cron_keep_entries"), "copg_cron_keep_entries");
        $ti->setSize(6);
        $ti->setSuffix($this->getPlugin()->txt("copg_entries"));
        $ti->setInfo($this->getPlugin()->txt("copg_cron_keep_entries_info"));
        $ti->setValue($settings->get("copg_cron_keep_entries"));
        $a_form->addItem($ti);
    }

    public function saveCustomSettings(ilPropertyFormGUI $a_form)
    {
        global $DIC;

        $settings = $DIC->settings();

        $settings->set("copg_cron_days", $a_form->getInput("copg_cron_days"));
        $settings->set("copg_cron_keep_entries", $a_form->getInput("copg_cron_keep_entries"));

        return true;
    }

}