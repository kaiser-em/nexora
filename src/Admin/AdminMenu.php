<?php

declare(strict_types=1);

namespace Silao\Admin;

final class AdminMenu
{
    public function registerMenus(): void
    {
        // 1. Menu Racine Silao -> manage_silao
        add_menu_page(
            'Silao',
            'Silao',
            'manage_silao',
            'silao_dashboard',
            [$this, 'renderDashboardPage'],
            'dashicons-calendar-alt',
            26
        );

        // 2. Sous-menu Dashboard
        add_submenu_page(
            'silao_dashboard',
            'Tableau de bord',
            'Tableau de bord',
            'manage_silao',
            'silao_dashboard',
            [$this, 'renderDashboardPage']
        );

        // 3. Sous-menu Modèles de Réservation -> manage_silao
        add_submenu_page(
            'silao_dashboard',
            'Modèles de Réservation',
            'Modèles',
            'manage_silao',
            'silao_booking_models',
            [$this, 'renderBookingModelsPage']
        );

        // 4. Sous-menu Réservations -> manage_silao_bookings
        add_submenu_page(
            'silao_dashboard',
            'Réservations',
            'Réservations',
            'manage_silao_bookings',
            'silao_bookings',
            [$this, 'renderBookingsPage']
        );

        // 5. Sous-menu Ressources -> manage_silao
        add_submenu_page(
            'silao_dashboard',
            'Ressources',
            'Ressources',
            'manage_silao',
            'silao_resources',
            [$this, 'renderResourcesPage']
        );

        // 6. Sous-menu Clients -> manage_silao
        add_submenu_page(
            'silao_dashboard',
            'Clients',
            'Clients',
            'manage_silao',
            'silao_customers',
            [$this, 'renderCustomersPage']
        );
    }

    public function renderDashboardPage(): void
    {
        $this->renderView('dashboard');
    }

    public function renderBookingModelsPage(): void
    {
        $this->renderView('booking-models');
    }

    public function renderBookingsPage(): void
    {
        $this->renderView('bookings');
    }

    public function renderResourcesPage(): void
    {
        $this->renderView('resources');
    }

    public function renderCustomersPage(): void
    {
        $this->renderView('customers');
    }

    private function renderView(string $viewName): void
    {
        $file = __DIR__ . '/View/' . $viewName . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
}