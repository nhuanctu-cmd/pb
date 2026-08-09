<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\PosService;
use App\Services\InventoryService;
use App\Models\BookingModel;
use App\Models\PlayerModel;
use CodeIgniter\HTTP\ResponseInterface;

class PosController extends BaseController
{
    protected PosService $posService;
    protected InventoryService $inventoryService;
    protected BookingModel $bookingModel;
    protected PlayerModel $playerModel;

    public function __construct()
    {
        $this->posService = new PosService();
        $this->inventoryService = new InventoryService();
        $this->bookingModel = new BookingModel();
        $this->playerModel = new PlayerModel();
    }

    /**
     * POS Counter page
     */
    public function index(): string
    {
        $tenantId = session()->get('tenant_id');
        $branchId = session()->get('branch_id');

        // Create new order
        $order = $this->posService->createOrder($tenantId, $branchId, session()->get('user_id'));

        $data = [
            'order' => $order,
            'categories' => $this->posService->getCategoriesWithProducts($tenantId, $branchId),
        ];

        return view('admin/pos/counter', $data);
    }

    /**
     * Get order details (AJAX)
     */
    public function getOrder(int $orderId): ResponseInterface
    {
        $order = $this->posService->loadOrder($orderId);
        if (!$order) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Order not found']);
        }

        $items = $this->posService->getOrderItems($orderId);

        return $this->response->setJSON([
            'order' => $order,
            'items' => $items,
        ]);
    }

    /**
     * Add item to order (AJAX)
     */
    public function addItem(int $orderId): ResponseInterface
    {
        $tenantId = session()->get('tenant_id');
        $productId = (int)$this->request->getPost('product_id');
        $quantity = (int)$this->request->getPost('quantity', 1);

        $this->posService->loadOrder($orderId);

        try {
            $this->posService->addItem($tenantId, $productId, $quantity);
            $order = $this->posService->getCurrentOrder();
            $items = $this->posService->getOrderItems($orderId);

            return $this->response->setJSON([
                'success' => true,
                'order' => $order,
                'items' => $items,
                'message' => 'Item added successfully',
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove item from order (AJAX)
     */
    public function removeItem(int $orderId, int $itemId): ResponseInterface
    {
        $this->posService->loadOrder($orderId);

        try {
            $this->posService->removeItem($itemId);
            $order = $this->posService->getCurrentOrder();
            $items = $this->posService->getOrderItems($orderId);

            return $this->response->setJSON([
                'success' => true,
                'order' => $order,
                'items' => $items,
                'message' => 'Item removed successfully',
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update item quantity (AJAX)
     */
    public function updateItem(int $itemId): ResponseInterface
    {
        $orderId = (int)$this->request->getPost('order_id');
        $newQuantity = (int)$this->request->getPost('quantity');

        $this->posService->loadOrder($orderId);

        try {
            $this->posService->updateItemQuantity($itemId, $newQuantity);
            $order = $this->posService->getCurrentOrder();
            $items = $this->posService->getOrderItems($orderId);

            return $this->response->setJSON([
                'success' => true,
                'order' => $order,
                'items' => $items,
                'message' => 'Quantity updated successfully',
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Attach booking to order (AJAX)
     */
    public function attachBooking(int $orderId): ResponseInterface
    {
        $bookingId = (int)$this->request->getPost('booking_id');

        $this->posService->loadOrder($orderId);

        try {
            $this->posService->attachBooking($bookingId);
            $order = $this->posService->getCurrentOrder();

            return $this->response->setJSON([
                'success' => true,
                'order' => $order,
                'message' => 'Booking attached successfully',
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Attach player to order (AJAX)
     */
    public function attachPlayer(int $orderId): ResponseInterface
    {
        $playerId = (int)$this->request->getPost('player_id');

        $this->posService->loadOrder($orderId);

        try {
            $this->posService->attachPlayer($playerId);
            $order = $this->posService->getCurrentOrder();

            return $this->response->setJSON([
                'success' => true,
                'order' => $order,
                'message' => 'Player attached successfully',
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Search products (AJAX)
     */
    public function searchProducts(int $orderId): ResponseInterface
    {
        $tenantId = session()->get('tenant_id');
        $branchId = session()->get('branch_id');
        $keyword = $this->request->getGet('q');

        if (strlen($keyword) < 2) {
            return $this->response->setJSON(['products' => []]);
        }

        $products = $this->posService->searchProducts($tenantId, $branchId, $keyword);

        return $this->response->setJSON(['products' => $products]);
    }

    /**
     * Search bookings (AJAX)
     */
    public function searchBookings(): ResponseInterface
    {
        $tenantId = session()->get('tenant_id');
        $branchId = session()->get('branch_id');
        $keyword = $this->request->getGet('q');

        $bookings = $this->bookingModel
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->groupStart()
                ->like('booking_code', $keyword)
                ->orLike('customer_name', $keyword)
                ->orLike('customer_phone', $keyword)
            ->groupEnd()
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->findAll();

        return $this->response->setJSON(['bookings' => $bookings]);
    }

    /**
     * Search players (AJAX)
     */
    public function searchPlayers(): ResponseInterface
    {
        $tenantId = session()->get('tenant_id');
        $keyword = $this->request->getGet('q');

        $players = $this->playerModel
            ->where('tenant_id', $tenantId)
            ->groupStart()
                ->like('fullname', $keyword)
                ->orLike('phone', $keyword)
                ->orLike('email', $keyword)
            ->groupEnd()
            ->orderBy('fullname', 'ASC')
            ->limit(20)
            ->findAll();

        return $this->response->setJSON(['players' => $players]);
    }

    /**
     * Checkout order (AJAX)
     */
    public function checkout(int $orderId): ResponseInterface
    {
        $paidAmount = (float)$this->request->getPost('paid_amount');
        $note = $this->request->getPost('note');

        $this->posService->loadOrder($orderId);

        try {
            $success = $this->posService->checkout($paidAmount, $note);

            if ($success) {
                $order = $this->posService->getCurrentOrder();

                return $this->response->setJSON([
                    'success' => true,
                    'order' => $order,
                    'message' => 'Payment successful',
                ]);
            }

            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Payment failed',
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel order (AJAX)
     */
    public function cancel(int $orderId): ResponseInterface
    {
        $reason = $this->request->getPost('reason');

        try {
            $success = $this->posService->cancelOrder($orderId, $reason);

            if ($success) {
                // Create new order after cancellation
                $tenantId = session()->get('tenant_id');
                $branchId = session()->get('branch_id');
                $newOrder = $this->posService->createOrder($tenantId, $branchId, session()->get('user_id'));

                return $this->response->setJSON([
                    'success' => true,
                    'order' => $newOrder,
                    'message' => 'Order cancelled successfully',
                ]);
            }

            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Cancellation failed',
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
