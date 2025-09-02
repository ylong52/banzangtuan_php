<?php

namespace App\Services;

use Carbon\Carbon;

class TimeSegmentService
{
    /**
     * 根据当前时间获取时间段
     * 
     * @param string $segmentType 时间段类型
     * @param Carbon|null $time 指定时间，默认为当前时间
     * @return array
     */
    public static function getTimeSegment($segmentType = 'quarter_hour', $time = null)
    {
        $time = $time ?: Carbon::now();
        
        switch ($segmentType) {
            case 'quarter_hour':
                return self::getQuarterHourSegment($time);
            case 'half_hour':
                return self::getHalfHourSegment($time);
            case 'hour':
                return self::getHourSegment($time);
            case 'minute':
                return self::getMinuteSegment($time);
            case 'ten_minutes':
                return self::getTenMinutesSegment($time);
            default:
                return self::getQuarterHourSegment($time);
        }
    }
    
    /**
     * 获取15分钟时间段
     */
    private static function getQuarterHourSegment(Carbon $time)
    {
        $minute = $time->minute;
        $hour = $time->hour;
        $date = $time->toDateString();
        
        if ($minute < 15) {
            $startTime = $date . ' ' . sprintf('%02d:00:00', $hour);
            $endTime = $date . ' ' . sprintf('%02d:14:59', $hour);
            $segment = '第1个15分钟';
        } elseif ($minute < 30) {
            $startTime = $date . ' ' . sprintf('%02d:15:00', $hour);
            $endTime = $date . ' ' . sprintf('%02d:29:59', $hour);
            $segment = '第2个15分钟';
        } elseif ($minute < 45) {
            $startTime = $date . ' ' . sprintf('%02d:30:00', $hour);
            $endTime = $date . ' ' . sprintf('%02d:44:59', $hour);
            $segment = '第3个15分钟';
        } else {
            $startTime = $date . ' ' . sprintf('%02d:45:00', $hour);
            $endTime = $date . ' ' . sprintf('%02d:59:59', $hour);
            $segment = '第4个15分钟';
        }
        
        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'segment_name' => $segment,
            'segment_type' => 'quarter_hour'
        ];
    }
    
    /**
     * 获取30分钟时间段
     */
    private static function getHalfHourSegment(Carbon $time)
    {
        $minute = $time->minute;
        $hour = $time->hour;
        $date = $time->toDateString();
        
        if ($minute < 30) {
            $startTime = $date . ' ' . sprintf('%02d:00:00', $hour);
            $endTime = $date . ' ' . sprintf('%02d:29:59', $hour);
            $segment = '前30分钟';
        } else {
            $startTime = $date . ' ' . sprintf('%02d:30:00', $hour);
            $endTime = $date . ' ' . sprintf('%02d:59:59', $hour);
            $segment = '后30分钟';
        }
        
        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'segment_name' => $segment,
            'segment_type' => 'half_hour'
        ];
    }
    
    /**
     * 获取整点时间段
     */
    private static function getHourSegment(Carbon $time)
    {
        $hour = $time->hour;
        $date = $time->toDateString();
        
        $startTime = $date . ' ' . sprintf('%02d:00:00', $hour);
        $endTime = $date . ' ' . sprintf('%02d:59:59', $hour);
        
        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'segment_name' => sprintf('%02d点整点', $hour),
            'segment_type' => 'hour'
        ];
    }
    
    /**
     * 获取10分钟时间段
     */
    private static function getTenMinutesSegment(Carbon $time)
    {
        $minute = $time->minute;
        $hour = $time->hour;
        $date = $time->toDateString();
        
        $segmentIndex = intval($minute / 10);
        $startMinute = $segmentIndex * 10;
        $endMinute = $startMinute + 9;
        
        $startTime = $date . ' ' . sprintf('%02d:%02d:00', $hour, $startMinute);
        $endTime = $date . ' ' . sprintf('%02d:%02d:59', $hour, $endMinute);
        
        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'segment_name' => sprintf('%02d:%02d-%02d:%02d', $hour, $startMinute, $hour, $endMinute),
            'segment_type' => 'ten_minutes'
        ];
    }
    
    /**
     * 获取分钟时间段（精确到秒）
     */
    private static function getMinuteSegment(Carbon $time)
    {
        $minute = $time->minute;
        $hour = $time->hour;
        $date = $time->toDateString();
        
        $startTime = $date . ' ' . sprintf('%02d:%02d:00', $hour, $minute);
        $endTime = $date . ' ' . sprintf('%02d:%02d:59', $hour, $minute);
        
        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'segment_name' => sprintf('%02d:%02d分钟', $hour, $minute),
            'segment_type' => 'minute'
        ];
    }
    
    /**
     * 判断指定时间是否在某个时间段内
     */
    public static function isInTimeSegment($checkTime, $segmentType = 'quarter_hour', $baseTime = null)
    {
        $segment = self::getTimeSegment($segmentType, $baseTime);
        $checkTimestamp = Carbon::parse($checkTime)->timestamp;
        $startTimestamp = Carbon::parse($segment['start_time'])->timestamp;
        $endTimestamp = Carbon::parse($segment['end_time'])->timestamp;
        
        return $checkTimestamp >= $startTimestamp && $checkTimestamp <= $endTimestamp;
    }
    
    /**
     * 获取指定时间段的所有记录
     */
    public static function getRecordsInTimeSegment($model, $segmentType = 'quarter_hour', $time = null, $timeField = 'created_at')
    {
        $segment = self::getTimeSegment($segmentType, $time);
        
        return $model->whereBetween($timeField, [$segment['start_time'], $segment['end_time']]);
    }
    
    /**
     * 获取预定义的时间段选项
     */
    public static function getTimeSegmentOptions()
    {
        return [
            'minute' => '按分钟段',
            'ten_minutes' => '10分钟段',
            'quarter_hour' => '15分钟段',
            'half_hour' => '30分钟段',
            'hour' => '整点段'
        ];
    }
    
    /**
     * 验证时间格式
     */
    public static function validateTimeFormat($time, $format = 'Y-m-d H:i:s')
    {
        try {
            $d = Carbon::createFromFormat($format, $time);
            return $d && $d->format($format) === $time;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取时间段统计信息
     */
    public static function getTimeSegmentStats($model, $segmentType = 'quarter_hour', $date = null, $timeField = 'created_at')
    {
        $date = $date ?: Carbon::now()->toDateString();
        $stats = [];
        
        switch ($segmentType) {
            case 'quarter_hour':
                // 每天96个15分钟段
                for ($hour = 0; $hour < 24; $hour++) {
                    for ($quarter = 0; $quarter < 4; $quarter++) {
                        $startMinute = $quarter * 15;
                        $endMinute = $startMinute + 14;
                        $startTime = $date . ' ' . sprintf('%02d:%02d:00', $hour, $startMinute);
                        $endTime = $date . ' ' . sprintf('%02d:%02d:59', $hour, $endMinute);
                        
                        $count = $model->whereBetween($timeField, [$startTime, $endTime])->count();
                        $stats[] = [
                            'segment' => sprintf('%02d:%02d-%02d:%02d', $hour, $startMinute, $hour, $endMinute),
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'count' => $count
                        ];
                    }
                }
                break;
                
            case 'hour':
                // 每天24个小时段
                for ($hour = 0; $hour < 24; $hour++) {
                    $startTime = $date . ' ' . sprintf('%02d:00:00', $hour);
                    $endTime = $date . ' ' . sprintf('%02d:59:59', $hour);
                    
                    $count = $model->whereBetween($timeField, [$startTime, $endTime])->count();
                    $stats[] = [
                        'segment' => sprintf('%02d:00-%02d:59', $hour, $hour),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'count' => $count
                    ];
                }
                break;
        }
        
        return $stats;
    }
}
