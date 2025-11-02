<?php
namespace addon\easypay\model;

use think\Model;

/**
 * Easypay支付日志模型
 */
class EasypayLog extends Model
{
    /**
     * 表名
     */
    protected $name = 'easypay_log';

    /**
     * 自动写入时间戳
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间字段
     */
    protected $updateTime = 'update_time';

    /**
     * 状态列表
     */
    const STATUS_PENDING = 0;    // 待支付
    const STATUS_PAID = 1;       // 已支付
    const STATUS_FAILED = 2;     // 支付失败
    const STATUS_CANCELLED = 3;  // 已取消

    /**
     * 获取状态文本
     * @param int $status 状态值
     * @return string 状态文本
     */
    public static function getStatusText($status)
    {
        $statusMap = [
            self::STATUS_PENDING => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_FAILED => '支付失败',
            self::STATUS_CANCELLED => '已取消',
        ];

        return $statusMap[$status] ?? '未知';
    }

    /**
     * 根据商户订单号查询日志
     * @param string $outTradeNo 商户订单号
     * @return array|null 日志信息
     */
    public static function getByOutTradeNo($outTradeNo)
    {
        return self::where('out_trade_no', $outTradeNo)
            ->order('id DESC')
            ->find();
    }

    /**
     * 根据第三方订单号查询日志
     * @param string $tradeNo 第三方订单号
     * @return array|null 日志信息
     */
    public static function getByTradeNo($tradeNo)
    {
        return self::where('trade_no', $tradeNo)
            ->order('id DESC')
            ->find();
    }

    /**
     * 创建支付日志
     * @param array $data 日志数据
     * @return bool 创建结果
     */
    public static function createLog($data)
    {
        try {
            $log = new self();
            $log->out_trade_no = $data['out_trade_no'] ?? '';
            $log->trade_no = $data['trade_no'] ?? '';
            $log->pay_type = $data['pay_type'] ?? '';
            $log->money = $data['money'] ?? 0;
            $log->status = $data['status'] ?? self::STATUS_PENDING;
            $log->request_data = $data['request_data'] ?? '';
            $log->response_data = $data['response_data'] ?? '';
            $log->notify_data = $data['notify_data'] ?? '';
            $log->pay_url = $data['pay_url'] ?? '';
            $log->error_msg = $data['error_msg'] ?? '';
            $log->pay_time = $data['pay_time'] ?? 0;
            $log->client_ip = $data['client_ip'] ?? '';
            $log->user_agent = $data['user_agent'] ?? '';
            
            return $log->save();
        } catch (\Exception $e) {
            error_log('[Easypay] 创建支付日志失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 更新支付日志
     * @param int $logId 日志ID
     * @param array $data 更新数据
     * @return bool 更新结果
     */
    public static function updateLog($logId, $data)
    {
        try {
            $log = self::find($logId);
            if (!$log) {
                return false;
            }

            // 更新字段
            if (isset($data['trade_no'])) {
                $log->trade_no = $data['trade_no'];
            }
            if (isset($data['status'])) {
                $log->status = $data['status'];
            }
            if (isset($data['response_data'])) {
                $log->response_data = $data['response_data'];
            }
            if (isset($data['notify_data'])) {
                $log->notify_data = $data['notify_data'];
            }
            if (isset($data['pay_url'])) {
                $log->pay_url = $data['pay_url'];
            }
            if (isset($data['error_msg'])) {
                $log->error_msg = $data['error_msg'];
            }
            if (isset($data['pay_time'])) {
                $log->pay_time = $data['pay_time'];
            }

            return $log->save();
        } catch (\Exception $e) {
            error_log('[Easypay] 更新支付日志失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 标记订单为已支付
     * @param string $outTradeNo 商户订单号
     * @param string $tradeNo 第三方订单号
     * @param array $notifyData 回调数据
     * @return bool 更新结果
     */
    public static function markAsPaid($outTradeNo, $tradeNo, $notifyData = [])
    {
        try {
            $log = self::getByOutTradeNo($outTradeNo);
            if (!$log) {
                return false;
            }

            $log->trade_no = $tradeNo;
            $log->status = self::STATUS_PAID;
            $log->pay_time = time();
            if (!empty($notifyData)) {
                $log->notify_data = json_encode($notifyData);
            }

            return $log->save();
        } catch (\Exception $e) {
            error_log('[Easypay] 标记订单支付失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取支付统计
     * @param array $where 查询条件
     * @return array 统计数据
     */
    public static function getStatistics($where = [])
    {
        try {
            $query = self::where($where);
            
            $total = $query->count();
            $paid = $query->where('status', self::STATUS_PAID)->count();
            $pending = $query->where('status', self::STATUS_PENDING)->count();
            $failed = $query->where('status', self::STATUS_FAILED)->count();
            $cancelled = $query->where('status', self::STATUS_CANCELLED)->count();
            
            $totalAmount = $query->where('status', self::STATUS_PAID)->sum('money');

            return [
                'total' => $total,
                'paid' => $paid,
                'pending' => $pending,
                'failed' => $failed,
                'cancelled' => $cancelled,
                'total_amount' => $totalAmount,
            ];
        } catch (\Exception $e) {
            error_log('[Easypay] 获取支付统计失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 清理过期日志
     * @param int $days 保留天数
     * @return int 清理数量
     */
    public static function cleanExpiredLogs($days = 30)
    {
        try {
            $expireTime = time() - ($days * 24 * 3600);
            return self::where('create_time', '<', $expireTime)
                ->where('status', 'in', [self::STATUS_FAILED, self::STATUS_CANCELLED])
                ->delete();
        } catch (\Exception $e) {
            error_log('[Easypay] 清理过期日志失败: ' . $e->getMessage());
            return 0;
        }
    }
}