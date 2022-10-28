<?php

class LfPCFileListMods
{
    public static function _deleteHistoryLowerEqualThan(
        string $parent_type,
        int $page_id,
        string $lang,
        int $old_nr
    ) {
        $log = ilLoggerFactory::getLogger("copg");

        $file_ids = self::_getHistoryUsagesLowerEqualThan(
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

        foreach ($file_ids as $file_id) {
            if (ilObject::_lookupType($file_id) == "file") {
                $file = new ilObjFile($file_id, false);
                $usages = $file->getUsages();
                $log->debug("...check deletion of file $file_id. Usages: " . count($usages));
                if (count($usages) == 0) {
                    $log->debug("Deleting File ID: " . $file_id);
                    $file->delete();
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

        $file_ids = array();
        $set = $ilDB->query("SELECT DISTINCT(id) FROM file_usage" .
            " WHERE usage_type = " . $ilDB->quote($a_type, "text") .
            " AND usage_id = " . $ilDB->quote($a_id, "integer") .
            " AND usage_lang = " . $ilDB->quote($a_lang, "text") .
            $and_hist);

        while ($row = $ilDB->fetchAssoc($set)) {
            $file_ids[] = $row["id"];
        }
        return $file_ids;
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

        $q = "DELETE FROM file_usage WHERE usage_type = "
            . $ilDB->quote($a_type, "text") . " AND usage_id = "
            . $ilDB->quote((int) $a_id, "integer") . " AND usage_lang= "
            . $ilDB->quote($a_lang, "text")
            . $and_hist;

        $log->debug($q);
        $ilDB->manipulate($q);
    }

}