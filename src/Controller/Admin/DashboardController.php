<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin_user_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('News Aggregator Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkTo(UserCrudController::class, 'Users', 'fa fa-users');
        yield MenuItem::linkTo(ConfirmationCodeCrudController::class, 'Confirmation Codes', 'fa fa-envelope');
        yield MenuItem::linkTo(NewsCrudController::class, 'News', 'fa fa-newspaper');
        yield MenuItem::linkTo(NewsSourceCrudController::class, 'News Sources', 'fa fa-rss');
    }
}
