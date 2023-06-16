<?php


namespace App\Common\Classes;


use DateTime;

abstract class DatabaseExportDate
{
    private const DATE_FILE_NAME = 'database_export.info';

    /**
     * Save database export date in file.
     */
    public static function saveDatabaseExportDate() {
        $date = new DateTime();
        file_put_contents(self::DATE_FILE_NAME, json_encode($date));
    }

    /**
     * Get last export date from database export information file.
     * @return mixed PHPDatetime object in JSON.
     */
    public static function getDatabaseExportDate() {
        $date = file_get_contents(self::DATE_FILE_NAME);
        return json_decode(trim($date), TRUE);
    }
}