<?php
namespace addon\yipay\model;

use think\Model;

/**
 * 易支付日志数据模型
 */
class YipayLog extends Model
{
    /**
     * 数据表名
     */
    protected $name = 'yipay_log';

    /**
     * 自动写入时间戳
     */
    protected $autoWriteTimestamp = false;

    /**
     * 创建支付日志
     * @param array $data 日志数据
     * @return int|false 日志ID或失败
     */
    public function createLog($data)
    {
        $log = [
            'trade_no' => isset($data['trade_no']) ? $data['trade_no'] : '',
            'out_trade_no' => isset($data['out_trade_no']) ? $data['out_trade_no'] : '',
            'pay_type' => isset($data['pay_type']) ? $data['pay_type'] : '',
            'money' => isset($data['money']) ? $data['money'] : 0,
            'status' => isset($data['status']) ? $data['status'] : 0,
            'notify_data' => isset($data['notify_data']) ? $data['notify_data'] : '',
            'create_time' => time(),
            'pay_time' => 0,
        ];

        $result = $this->save($log);

        return $result ? $this->id : false;
    }

    /**
     * 更新支付状态
     * @param string $tradeNo 商户订单号
     * @param int $status 支付状态
     * @param array $notifyData 回调数据
     * @return bool 更新结果
     */
    public function updateStatus($tradeNo, $status, $notifyData = [])
    {
        $data = [
            'status' => $status,
        ];

        if ($status == 1) {
            $data['pay_time'] = time();
        }

        if (!empty($notifyData)) {
            $data['notify_data'] = json_encode($notifyData, JSON_UNESCAPED_UNICODE);
        }

        return $this->where('trade_no', $tradeNo)->update($data);
    }

    /**
     * 根据商户订单号查询日志
     * @param string $tradeNo 商户订单号
     * @return array|null 日志信息
     */
    public function getLogByTradeNo($tradeNo)
    {
        return $this->where('trade_no', $tradeNo)->find();
    }

    /**
     * 根据易支付订单号查询日志
     * @param string $outTradeNo 易支付订单号
     * @return array|null 日志信息
     */
    public function getLogByOutTradeNo($outTradeNo)
    {
        return $this->where('out_trade_no', $outTradeNo)->find();
    }

    /**
     * 获取日志列表
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array 日志列表
     */
    public function getLogList($where = [], $page = 1, $limit = 20)
    {
        $query = $this;

        if (isset($where['trade_no']) && $where['trade_no']) {
            $query = $query->where('trade_no', 'like', '%' . $where['trade_no'] . '%');
        }

        if (isset($where['pay_type']) && $where['pay_type']) {
            $query = $query->where('pay_type', $where['pay_type']);
        }

        if (isset($where['status']) && $where['status'] !== '') {
            $query = $query->where('status', $where['status']);
        }

        $total = $query->count();
        $list = $query->order('create_time', 'desc')
            ->page($page, $limit)
            ->select();

        return [
            'total' => $total,
            'list' => $list ? $list->toArray() : [],
        ];
    }

    /**
     * 统计数据
     * @param array $where 查询条件
     * @return array 统计结果
     */
    public function getStatistics($where = [])
    {
        $query = $this;

        if (isset($where['start_time']) && $where['start_time']) {
            $query = $query->where('create_time', '>=', $where['start_time']);
        }

        if (isset($where['end_time']) && $where['end_time']) {
            $query = $query->where('create_time', '<=', $where['end_time']);
        }

        return [
            'total_count' => $query->count(),
            'success_count' => $query->where('status', 1)->count(),
            'total_money' => $query->where('status', 1)->sum('money'),
        ];
    }
}
