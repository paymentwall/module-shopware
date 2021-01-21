<?php

namespace Paymentwall\Components\Services;

use Doctrine\DBAL\Connection;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class OrderService
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function setOrderStatus($orderId, $orderStatusId)
    {
        return $this->connection->createQueryBuilder()
            ->update('s_order')
            ->set('status', ':orderStatusId')
            ->where('id = :orderId')
            ->setParameters([
                ':orderId' => $orderId,
                ':orderStatusId' => $orderStatusId,
            ])
            ->execute();
    }

    public function getOrderStatus($orderId)
    {
        return $this->connection->createQueryBuilder()
            ->select('status')
            ->from('s_order')
            ->where('id = :orderId')
            ->setParameters([
                ':orderId' => $orderId
            ])
            ->execute()
            ->fetchColumn();
    }

    public function isOrderHistoryEmpty($orderId)
    {
        return !$this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_order_history')
            ->where('orderId = :orderId')
            ->setParameters([
                ':orderId' => $orderId,
            ])
            ->execute()
            ->fetchColumn();
    }

    public function updateTransactionId($transactionId, $orderId)
    {
        return $this->connection->createQueryBuilder()
            ->update('s_order')
            ->set('transactionID', ':transactionId')
            ->where('id = :orderId')
            ->setParameters([
                ':orderId' => $orderId,
                ':transactionId' => $transactionId,
            ])
            ->execute();
    }

    public function getPaymentIdByOrderId($orderId)
    {
        return $this->connection->createQueryBuilder()
            ->select('paymentID')
            ->from('s_order')
            ->where('id = :orderId')
            ->setParameters([
                ':orderId' => $orderId,
            ])
            ->execute()
            ->fetchColumn();
    }

    public function loadOrderRepositoryById($orderId)
    {
        $repository = Shopware()->Models()->getRepository(Order::class);
        return $repository->find($orderId);
    }

    public function isOrderHistoryHasPaidStatus($orderId)
    {
        return $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_order_history')
            ->where('orderId = :orderId')
            ->andWhere('payment_status_id = :payment_status_id')
            ->setParameters([
                ':orderId' => $orderId,
                ':payment_status_id' => Status::PAYMENT_STATE_COMPLETELY_PAID
            ])
            ->execute()
            ->fetchColumn();
    }
}
