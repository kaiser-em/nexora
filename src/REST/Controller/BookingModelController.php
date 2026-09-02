<?php

declare(strict_types=1);

namespace Silao\REST\Controller;

use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\REST\RestErrorMapper;
use Silao\REST\Schema\BookingModelSchema;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class BookingModelController extends AbstractRestController
{
    public function getPublished(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $repo = $this->container->get(BookingModelRepositoryInterface::class);
            $models = $repo->findAllPublished();

            $data = [];
            foreach ($models as $m) {
                $data[] = [
                    'model_id' => $m->id()->toString(),
                    'slug' => $m->slug(),
                    'name' => $m->name(),
                    'description' => $m->description(),
                    'base_price' => [
                        'currency' => $m->basePrice()->currency->code,
                        'amount' => $m->basePrice()->amount,
                        'formatted' => $m->basePrice()->format(),
                    ],
                    'fields' => array_map(static fn($f) => [
                        'name' => $f->name,
                        'type' => $f->type->value,
                        'label' => $f->label,
                        'is_required' => $f->isRequired,
                        'default_value' => $f->defaultValue,
                    ], array_values($m->fields())),
                    'options' => array_map(static fn($o) => [
                        'id' => $o->id,
                        'code' => $o->code,
                        'name' => $o->name,
                        'description' => $o->description,
                        'pricing_type' => $o->pricingType->value,
                        'unit_price' => $o->unitPrice->amount,
                        'formatted_price' => $o->unitPrice->format(),
                        'min_quantity' => $o->minQuantity,
                        'max_quantity' => $o->maxQuantity,
                        'is_mandatory' => $o->isMandatory,
                    ], array_values($m->options())),
                ];
            }

            return new WP_REST_Response(['success' => true, 'data' => $data], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function getOne(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = BookingModelId::fromString((string) $request->get_param('id'));
            $repo = $this->container->get(BookingModelRepositoryInterface::class);
            $m = $repo->findById($id);

            if ($m === null) {
                return new WP_Error('silao_rest_not_found', 'Booking model not found.', ['status' => 404]);
            }

            if (!$m->status()->isPublished() && !current_user_can('manage_silao')) {
                return new WP_Error('silao_rest_not_found', 'Booking model not found.', ['status' => 404]);
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'model_id' => $m->id()->toString(),
                    'slug' => $m->slug(),
                    'name' => $m->name(),
                    'description' => $m->description(),
                    'status' => $m->status()->value,
                    'base_price' => [
                        'currency' => $m->basePrice()->currency->code,
                        'amount' => $m->basePrice()->amount,
                        'formatted' => $m->basePrice()->format(),
                    ],
                ],
            ], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function create(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $model = BookingModelSchema::toBookingModel($request);
            $repo = $this->container->get(BookingModelRepositoryInterface::class);
            $repo->save($model);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'model_id' => $model->id()->toString(),
                    'slug' => $model->slug(),
                    'name' => $model->name(),
                    'status' => $model->status()->value,
                ],
            ], 201);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function replace(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = BookingModelId::fromString((string) $request->get_param('id'));
            $repo = $this->container->get(BookingModelRepositoryInterface::class);

            if ($repo->findById($id) === null) {
                return new WP_Error('silao_rest_not_found', 'Booking model not found.', ['status' => 404]);
            }

            // PUT: Full replacement
            $newModel = BookingModelSchema::toBookingModel($request, $id);
            $repo->save($newModel);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'model_id' => $newModel->id()->toString(),
                    'slug' => $newModel->slug(),
                    'name' => $newModel->name(),
                    'status' => $newModel->status()->value,
                ],
            ], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function patch(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = BookingModelId::fromString((string) $request->get_param('id'));
            $repo = $this->container->get(BookingModelRepositoryInterface::class);
            $model = $repo->findById($id);

            if ($model === null) {
                return new WP_Error('silao_rest_not_found', 'Booking model not found.', ['status' => 404]);
            }

            $name = $request->get_param('name');
            $desc = $request->get_param('description');
            if ($name !== null || $desc !== null) {
                $model->updateDetails((string) ($name ?? $model->name()), (string) ($desc ?? $model->description()));
            }

            $basePrice = $request->get_param('base_price');
            if ($basePrice !== null) {
                $currency = $model->basePrice()->currency;
                $model->updateBasePrice(Money::of((int) $basePrice, $currency));
            }

            $status = $request->get_param('status');
            if ($status === 'published') {
                $model->publish();
            } elseif ($status === 'archived') {
                $model->archive();
            } elseif ($status === 'draft') {
                $model->setAsDraft();
            }

            $repo->save($model);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'model_id' => $model->id()->toString(),
                    'status' => $model->status()->value,
                ],
            ], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }
}