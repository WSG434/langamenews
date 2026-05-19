<?php

namespace App\Controller\Admin;

use App\Entity\NewsSource;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class NewsSourceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NewsSource::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['name' => 'ASC'])
            ->setEntityLabelInSingular('News Source')
            ->setEntityLabelInPlural('News Sources');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('code')->setHelp('Unique identifier, e.g. lenta, hackernews');
        yield TextField::new('name');
        yield ChoiceField::new('type')->setChoices([
            'RSS' => NewsSource::TYPE_RSS,
            'JSON API' => NewsSource::TYPE_JSON_API,
            'HTML' => NewsSource::TYPE_HTML,
        ]);
        yield UrlField::new('url');
        yield BooleanField::new('enabled');
        yield DateTimeField::new('lastFetchedAt')->hideOnForm();
    }
}
