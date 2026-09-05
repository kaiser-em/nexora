<?php

declare(strict_types=1);

namespace Silao\Frontend;

use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;

final class Shortcode
{
    private static bool $hasRendered = false;

    public function __construct(
        private readonly BookingModelRepositoryInterface $modelRepository
    ) {
    }

    public function register(): void
    {
        add_shortcode('silao_booking', [$this, 'render']);
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public function render(array|string $atts = []): string
    {
        self::$hasRendered = true;

        $parsedAtts = is_array($atts) ? $atts : [];
        $modelIdentifier = (string) ($parsedAtts['model'] ?? $parsedAtts['id'] ?? '');

        if (trim($modelIdentifier) === '') {
            if (current_user_can('manage_silao')) {
                return '<div class="silao-notice silao-notice-error"><p>' . esc_html__('Silao: Veuillez spécifier un identifiant de modèle (ex: [silao_booking model="transfer-vip"]).', 'silao') . '</p></div>';
            }
            return '';
        }

        // Try lookup by Slug first, then by ID
        $model = $this->modelRepository->findBySlug($modelIdentifier);
        if ($model === null) {
            try {
                $model = $this->modelRepository->findById(BookingModelId::fromString($modelIdentifier));
            } catch (\Throwable) {
                $model = null;
            }
        }

        if ($model === null || !$model->status()->isPublished()) {
            if (current_user_can('manage_silao')) {
                return '<div class="silao-notice silao-notice-warning"><p>' . sprintf(
                    /* translators: %s: Model identifier */
                    esc_html__('Silao: Le modèle de réservation "%s" est introuvable ou n\'est pas publié.', 'silao'),
                    esc_html($modelIdentifier)
                ) . '</p></div>';
            }
            return '';
        }

        $modelIdAttr = esc_attr($model->id()->toString());
        $modelSlugAttr = esc_attr($model->slug());

        return sprintf(
            '<div class="silao-booking-widget" data-model-id="%s" data-model-slug="%s">
                <noscript>
                    <div class="silao-widget-noscript">
                        <p>%s</p>
                    </div>
                </noscript>
                <div class="silao-widget-loader" role="status" aria-live="polite">
                    <span class="silao-spinner"></span>
                    <p>%s</p>
                </div>
            </div>',
            $modelIdAttr,
            $modelSlugAttr,
            esc_html__('JavaScript est requis pour utiliser le formulaire de réservation en direct.', 'silao'),
            esc_html__('Chargement du formulaire de réservation...', 'silao')
        );
    }

    public static function hasRendered(): bool
    {
        return self::$hasRendered;
    }
}