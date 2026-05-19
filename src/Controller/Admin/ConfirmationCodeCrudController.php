<?php

namespace App\Controller\Admin;

use App\Entity\ConfirmationCode;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class ConfirmationCodeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ConfirmationCode::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setEntityLabelInSingular('Confirmation Code')
            ->setEntityLabelInPlural('Confirmation Codes')
            ->setPageTitle(Crud::PAGE_INDEX, 'Confirmation Codes');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('user');
        yield TextField::new('code')->setMaxLength(6);
        yield TextField::new('status');
        yield DateTimeField::new('createdAt')->hideOnForm();
        yield DateTimeField::new('sentAt')->hideOnForm();
        yield DateTimeField::new('confirmedAt')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(
            ChoiceFilter::new('status')->setChoices([
                'Pending' => ConfirmationCode::STATUS_PENDING,
                'Sent' => ConfirmationCode::STATUS_SENT,
                'Failed' => ConfirmationCode::STATUS_FAILED,
                'Confirmed' => ConfirmationCode::STATUS_CONFIRMED,
            ])
        );
    }
}
