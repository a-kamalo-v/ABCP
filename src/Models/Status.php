<?php

class Status
{
    public $statusId, $statusName;

    public static function getStatusName(int $id): string
    {
        $statusNames = [
            0 => 'Completed',
            1 => 'Pending',
            2 => 'Rejected',
        ];

        return $statusNames[$id];
    }
}
