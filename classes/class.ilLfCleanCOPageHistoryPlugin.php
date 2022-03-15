<?php

/* Copyright (c) 2022 Leifos GmbH, GPLv3*/


/**
 * Cleanup copage history
 * @author  Alex Killing <killing@leifos.de>
 */
class ilLfCleanCOPageHistoryPlugin extends ilCronHookPlugin
{
    const CTYPE = 'Services';
    const CNAME = 'Cron';
    const SLOT_ID = 'crnhk';
    const PNAME = 'LfCleanCOPageHistory';

    /**
     * @var \ilLfCleanCOPageHistoryPlugin|null
     */
    private static $instance = null;


    function getPluginName()
    {
        return "LfCleanCOPageHistory";
    }

    /**
     * @return \ilLfCleanCOPageHistoryPlugin|\ilPlugin|null
     */
    public static function getInstance()
    {
        if(self::$instance)
        {
            return self::$instance;
        }
        return self::$instance = ilPluginAdmin::getPluginObject(
            self::CTYPE,
            self::CNAME,
            self::SLOT_ID,
            self::PNAME
        );
    }

    /**
     * Init auto load
     */
    protected function init()
    {
        $this->includeClass("class.ilLfCleanCOPageHistoryCronjob.php");
    }

    /**
     * Init auto loader
     * @return void
     */
    protected function initAutoLoad()
    {
        /*
        spl_autoload_register(
            array($this,'autoLoad')
        );*/
    }

    function getCronJobInstances()
    {
        $job = new ilLfCleanCOPageHistoryCronjob();

        return array($job);
    }

    function getCronJobInstance($a_job_id)
    {
        return new ilLfCleanCOPageHistoryCronjob();
    }

}

?>
