<?php

class LfPCMediaObjectMods
{
    public static function _deleteHistoryLowerEqualThan(
        string $parent_type,
        int $page_id,
        string $lang,
        int $old_nr
    ) {
        $log = ilLoggerFactory::getLogger("copg");

        $mob_ids = self::_getHistoryUsagesLowerEqualThan(
            $parent_type . ":pg",
            $page_id,
            $old_nr,
            $lang
        );

        self::_deleteHistoryUsagesLowerEqualThan(
            $parent_type . ":pg",
            $page_id,
            $old_nr,
            $lang
        );

        foreach ($mob_ids as $mob_id) {
            $usages = ilObjMediaObject::lookupUsages($mob_id, true);
            $log->debug("...check deletion of mob $mob_id. Usages: ".count($usages));
            if (count($usages) == 0) {
                if (ilObject::_lookupType($mob_id) == "mob") {
                    $mob = new ilObjMediaObject($mob_id);
                    $log->debug("Deleting Mob ID: " . $mob_id);
                    $mob->delete();
                }
            }
        }
    }

    public static function _getHistoryUsagesLowerEqualThan(
        $a_type,
        $a_id,
        $a_usage_hist_nr,
        $a_lang = "-") : array
    {
        global $DIC;

        $ilDB = $DIC->database();
        $and_hist = " AND usage_hist_nr > 0 AND usage_hist_nr <= " . $ilDB->quote($a_usage_hist_nr, "integer");

        $mob_ids = array();
        $set = $ilDB->query("SELECT DISTINCT(id) FROM mob_usage" .
            " WHERE usage_type = " . $ilDB->quote($a_type, "text") .
            " AND usage_id = " . $ilDB->quote($a_id, "integer") .
            " AND usage_lang = " . $ilDB->quote($a_lang, "text") .
            $and_hist);

        while ($row = $ilDB->fetchAssoc($set)) {
            $mob_ids[] = $row["id"];
        }
        return $mob_ids;
    }

    public static function _deleteHistoryUsagesLowerEqualThan(
        $a_type,
        $a_id,
        $a_usage_hist_nr,
        $a_lang = "-")
    {
        global $DIC;

        $ilDB = $DIC->database();
        $log = ilLoggerFactory::getLogger("copg");

        $and_hist = " AND usage_hist_nr > 0 AND usage_hist_nr <= " . $ilDB->quote($a_usage_hist_nr, "integer");
        $q = "DELETE FROM mob_usage WHERE usage_type = " .
            $ilDB->quote($a_type, "text") .
            " AND usage_id= " . $ilDB->quote($a_id, "integer") .
            " AND usage_lang = " . $ilDB->quote($a_lang, "text") .
            $and_hist;
        $log->debug($q);
        $ilDB->manipulate($q);
    }

}