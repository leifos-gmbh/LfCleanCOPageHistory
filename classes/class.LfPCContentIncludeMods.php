<?php

class LfPCContentIncludeMods
{
    public static function _deleteHistoryLowerEqualThan(
        string $parent_type,
        int $page_id,
        string $lang,
        int $old_nr
    ) {
        $log = ilLoggerFactory::getLogger("copg");

        LfPageContentUsageMods::_deleteHistoryUsagesLowerEqualThan(
            "incl",
            $parent_type . ":pg",
            $page_id,
            $old_nr,
            $lang
        );
    }
}