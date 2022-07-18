<?php
namespace IceCatBundle\Lib;

use Carbon\Carbon;

trait IceCateHelper
{
    /**
     * @param $dateString
     * @return Carbon|null
     */
    public function getCarbonObjectForDateString($dateString)
    {
        if (empty($dateString)) {
            return null;
        }
        try {
            return Carbon::create($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }
}
