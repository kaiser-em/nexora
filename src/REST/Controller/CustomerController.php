<?php

declare(strict_types=1);

namespace Silao\REST\Controller;

use Silao\Domain\Customer\Repository\CustomerRepositoryInterface;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\REST\RestErrorMapper;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class CustomerController extends AbstractRestController
{
    public function getAll(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $emailParam = $request->get_param('email');
            $repo = $this->container->get(CustomerRepositoryInterface::class);

            if (is_string($emailParam) && trim($emailParam) !== '') {
                $customer = $repo->findByEmail(Email::fromString($emailParam));
                $data = $customer !== null ? [[
                    'customer_id' => $customer->id()->toString(),
                    'email' => $customer->email()->toString(),
                    'first_name' => $customer->firstName(),
                    'last_name' => $customer->lastName(),
                    'full_name' => $customer->fullName(),
                    'phone' => $customer->phone()?->toString(),
                    'is_guest' => $customer->isGuest(),
                ]] : [];

                return new WP_REST_Response(['success' => true, 'data' => $data], 200);
            }

            return new WP_REST_Response(['success' => true, 'data' => []], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }
}