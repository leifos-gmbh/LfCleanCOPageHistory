<?php

class LfPCSkillsMods
{
    public static function _deleteHistoryLowerEqualThan(
        string $parent_type,
        int $page_id,
        string $lang,
        int $old_nr
    ) {
        $log = ilLoggerFactory::getLogger("copg");

        LfPageContentUsageMods::_deleteHistoryUsagesLowerEqualThan(
            "skmg",
            $parent_type . ":pg",
            $page_id,
            $old_nr,
            $lang
        );
    }
}