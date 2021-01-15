<?php


namespace Manticoresearch\ESMigrator\DataType;

class Date implements DataType
{
    public function translate($estype, $mstypes = null)
    {
        $multiple = explode('||', $estype['format']);
        if (count($multiple)>1) {
            $estype['format'] = end($multiple);
        }
        switch ($estype['format']) {
            case 'epoch_second':
                $return = [
                'type' => 'timestamp',
                'transform' => function ($date) {
                    return $date;
                }
                ];
                break;
            case 'epoch_millis':
                $return = [
                'type' => 'bigint',
                'transform' => function ($date) {
                    return $date;
                }
                ];
                break;
            case 'date_optional_time ':
            case 'strict_date_optional_time ':
            case 'strict_date_optional_time_nanos ':
            case 'basic_date':
            case 'basic_date_time':
            case 'basic_date_time_no_millis':
            case 'basic_ordinal_date':
            case 'date':
            case 'strict_date':
            case 'date_hour':
            case 'strict_date_hour':
            case 'date_hour_minute':
            case 'strict_date_hour_minute':
            case 'date_hour_minute_second':
            case 'strict_date_hour_minute_second':
            case 'date_hour_minute_second_fraction':
            case 'strict_date_hour_minute_second_fraction':
            case 'date_hour_minute_second_millis':
            case 'strict_date_hour_minute_second_millis':
            case 'date_time':
            case 'strict_date_time':
            case 'date_time_no_millis':
            case 'strict_date_time_no_millis':
            case 'hour_minute':
            case 'strict_hour_minute':
            case 'hour_minute_second ':
            case 'strict_hour_minute_second':
            case 'hour_minute_second_fraction':
            case 'strict_hour_minute_second_fraction':
            case 'hour_minute_second_millis':
            case 'strict_hour_minute_second_millis':
            case 'time ':
            case 'strict_time':
            case 'time_no_millis':
            case 'strict_time_no_millis':
            case 't_time':
            case 'strict_t_time':
            case 't_time_no_millis':
            case 'strict_t_time_no_millis':
            case 'year':
            case 'strict_year':
            case 'year_month':
            case 'strict_year_month':
            case 'year_month_day':
            case 'strict_year_month_day':
                $return = [
                'type' => 'timestamp',
                'transform' => function ($date) {
                    return strtotime($date);
                }
                ];
                if (strpos($estype['format'], 'millis') !== false) {
                    $return['type'] = 'bigint';
                }
                break;
            default:
                $return = [
                'type' => 'string',
                'transform' => function ($date) {
                    return $date;
                }
                ];
                break;
        }
        return $return;
    }
}
