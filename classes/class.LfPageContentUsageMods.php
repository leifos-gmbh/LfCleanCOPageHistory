<?php

class LfPageContentUsageMods
{
    public static function _deleteHistoryUsagesLowerEqualThan(
        $pc_type,
        $a_type,
        $a_id,
        $a_usage_hist_nr,
        $a_lang = "-")
    {
        global $DIC;

        $ilDB = $DIC->database();
        $log = ilLoggerFactory::getLogger("copg");

        $and_hist = " AND usage_hist_nr > 0 AND usage_hist_nr <= " . $ilDB->quote($a_usage_hist_nr, "integer");

        $q = "DELETE FROM page_pc_usage WHERE usage_type = " .
            $ilDB->quote($a_type, "text") .
            " AND usage_id = " . $ilDB->quote((int) $a_id, "integer") .
            " AND usage_lang = " . $ilDB->quote($a_lang, "text") .
            $and_hist .
            " AND pc_type = " . $ilDB->quote($pc_type, "text");
        $log->debug($q);
        $ilDB->manipulate($q);
    }

}