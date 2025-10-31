<?php

namespace App\Tests\Order\Api;

use App\Auth\Domain\User;
use App\Auth\Repository\UserRepository;
use App\Cart\Domain\Cart;
use App\Cart\Domain\CartItem;
use App\Cart\Domain\Money;
use App\Cart\Domain\Port\CartRepositoryInterface;
use App\Cart\Domain\ProductId;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;
use App\Order\Domain\OrderStatus;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CancelOrderApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private OrderRepositoryInterface $orderRepository;
    private CartRepositoryInterface $cartRepository;
    private UserRepository $userRepository;
    private string $token;
    private User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->orderRepository = $container->get(OrderRepositoryInterface::class);
        $this->cartRepository = $container->get(CartRepositoryInterface::class);
        $this->userRepository = $container->get(UserRepository::class);

        // Create test user and get token
        $this->user = new User('test@example.com', password_hash('password123', PASSWORD_BCRYPT));
        $this->userRepository->save($this->user);
        $this->token = $this->getAuthToken('test@example.com', 'password123');
    }

    public function testCanCancelPendingOrder(): void
    {
        // Arrange
        $cart = $this->createCartWithItems($this->user->getId());
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PENDING);
        $this->orderRepository->save($order);

        // Act
        $this->client->request(
            'POST',
            '/api/orders/' . $orderId . '/cancel',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Order cancelled successfully', $responseData['message']);

        // Verify order is cancelled in DB
        $updatedOrder = $this->orderRepository->get($orderId);
        $this->assertTrue($updatedOrder->status()->isCancelled());
    }

    public function testCannotCancelPaidOrder(): void
    {
        // Arrange
        $cart = $this->createCartWithItems($this->user->getId());
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PAID, 'payment_123');
        $this->orderRepository->save($order);

        // Act
        $this->client->request(
            'POST',
            '/api/orders/' . $orderId . '/cancel',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testCannotCancelNonExistentOrder(): void
    {
        // Act
        $nonExistentOrderId = (string) OrderId::generate();
        $this->client->request(
            'POST',
            '/api/orders/' . $nonExistentOrderId . '/cancel',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Order not found', $responseData['error']);
    }

    public function testCannotCancelOrderWithoutAuthentication(): void
    {
        // Arrange
        $cart = $this->createCartWithItems($this->user->getId());
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PENDING);
        $this->orderRepository->save($order);

        // Act
        $this->client->request(
            'POST',
            '/api/orders/' . $orderId . '/cancel'
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    private function createCartWithItems(string $userId): Cart
    {
        $cart = Cart::createForUser($userId);

        $item = new CartItem(
            ProductId::fromString('prod-1'),
            'Product 1',
            Money::fromFloat(99.99),
            2
        );

        $cart->addItem($item);

        return $cart;
    }

    private function getAuthToken(string $email, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password])
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);
        return $response['token'];
    }
}

