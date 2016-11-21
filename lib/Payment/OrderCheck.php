<?php
/**
 * Created by PhpStorm.
 * User: luojinbo
 * Date: 16-8-1
 * Time: 下午5:11
 */

namespace Lib\Payment;
use Lib\Model\Order\OrderModel;
use Lib\Model\Customer\CustomerModel;
use Lib\Model\Order\OrderHistoryModel;

class OrderCheck {

    /**
     * 订单验证
     * @param $order_id
     * @return bool
     */
    public function checkOrder($appid, $order_id, $transaction_id, $payment)
    {
        $m = new OrderModel();
        $info = $m->getOrderInfo($appid, $order_id);
        if (!$info) {
            return false;
        }
        if(ORDER_STATUS_END == $info['order_status_id']) {
            return true;
        }else{
            if($m->acceptOrder($appid, $order_id, $transaction_id, $payment)) {
                // 新增积分
                $customerModel = new CustomerModel();
                $customerModel->addReward($appid, $info['customer_id'], $info['order_id']);
                $m->addOrderHistory($appid, $order_id, ORDER_STATUS_END, '', true);
                return true;
            }
            return false;
        }
    }
} 