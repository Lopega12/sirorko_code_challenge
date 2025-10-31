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

class GetOrderApiTest extends WebTestCase
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
        $this->user = new User('testget@example.com', password_hash('password123', PASSWORD_BCRYPT));
        $this->userRepository->save($this->user);
        $this->token = $this->getAuthToken('testget@example.com', 'password123');
    }

    public function testCanRetrieveOrderDetails(): void
    {
        // Arrange
        $cart = $this->createCartWithItems($this->user->getId());
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PENDING);
        $this->orderRepository->save($order);

        // Act
        $this->client->request(
            'GET',
            '/api/orders/' . $orderId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals((string) $orderId, $responseData['id']);
        $this->assertEquals('pending', $responseData['status']);
        $this->assertEquals('Pedido pendiente de procesamiento', $responseData['statusDescription']);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);
    }

    public function testRetrievedOrderContainsItemDetails(): void
    {
        // Arrange
        $cart = Cart::createForUser($this->user->getId());

        $item = new CartItem(
            ProductId::fromString('prod-123'),
            'Test Product',
            Money::fromFloat(25.50),
            3
        );

        $cart->addItem($item);
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total());
        $this->orderRepository->save($order);

        // Act
        $this->client->request(
            'GET',
            '/api/orders/' . $orderId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['items']);

        $item = $responseData['items'][0];
        $this->assertEquals('prod-123', $item['productId']);
        $this->assertEquals('Test Product', $item['name']);
        $this->assertEquals(25.50, $item['price']);
        $this->assertEquals(3, $item['quantity']);
        $this->assertEquals(76.50, $item['subtotal']);
    }

    public function testCanRetrievePaidOrderWithPaymentReference(): void
    {
        // Arrange
        $cart = $this->createCartWithItems($this->user->getId());
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $paymentRef = 'payment_abc123';
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PAID, $paymentRef);
        $this->orderRepository->save($order);

        // Act
        $this->client->request(
            'GET',
            '/api/orders/' . $orderId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('paid', $responseData['status']);
        $this->assertEquals($paymentRef, $responseData['paymentReference']);
    }

    public function testCannotRetrieveNonExistentOrder(): void
    {
        // Act
        $nonExistentOrderId = (string) OrderId::generate();
        $this->client->request(
            'GET',
            '/api/orders/' . $nonExistentOrderId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Order not found', $responseData['error']);
    }

    public function testCannotRetrieveOrderWithoutAuthentication(): void
    {
        // Arrange
        $cart = $this->createCartWithItems($this->user->getId());
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total());
        $this->orderRepository->save($order);

        // Act
        $this->client->request(
            'GET',
            '/api/orders/' . $orderId
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


