<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;
use Silao\Domain\Customer\Customer;
use Silao\Domain\Customer\Repository\CustomerRepositoryInterface;
use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\Infrastructure\Container\Container;
use Silao\REST\Controller\CustomerController;
use WP_REST_Request;

final class CustomerControllerTest extends TestCase
{
    public function testGetCustomerByEmail(): void
    {
        $customer = new Customer(CustomerId::fromString('c1'), Email::fromString('test@example.com'), 'John', 'Doe');

        $repo = $this->createMock(CustomerRepositoryInterface::class);
        $repo->method('findByEmail')->willReturn($customer);

        $container = new Container();
        $container->instance(CustomerRepositoryInterface::class, $repo);

        $controller = new CustomerController($container);

        $req = new WP_REST_Request('GET', '/silao/v1/customers');
        $req->set_param('email', 'test@example.com');

        $res = $controller->getAll($req);
        $this->assertSame(200, $res->get_status());
        $this->assertCount(1, $res->get_data()['data']);
        $this->assertSame('John Doe', $res->get_data()['data'][0]['full_name']);
    }
}