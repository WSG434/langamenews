<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email')->setRequired(false);
        yield TextField::new('telegramHandle', 'Telegram @username')->setRequired(false);
        yield IntegerField::new('telegramChatId', 'Telegram Chat ID')->hideOnForm()->onlyOnIndex();
        yield BooleanField::new('telegramLinked', 'TG linked')->hideOnForm()->onlyOnIndex();
        yield ChoiceField::new('roles')
            ->setChoices(['Admin' => 'ROLE_ADMIN', 'User' => 'ROLE_USER'])
            ->allowMultipleChoices()
            ->renderExpanded(false);
        yield BooleanField::new('isVerified');
        yield DateTimeField::new('createdAt')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(BooleanFilter::new('isVerified'));
    }
}
