# WeDevelop UX Table

**EXPERIMENTAL**: This package is still under development, everything is subject to change.

## Description

UX Table is a Data Table designed to work well
with [Symfony UX Turbo](https://symfony.com/bundles/ux-turbo/current/index.html)
and [Stimulus](https://stimulus.hotwired.dev/) (part of Symfony Encore).
This package aims to utilize the beautiful simplicity
of [Hypermedia-Driven Application](https://htmx.org/essays/hypermedia-driven-applications/) (HDA) architecture.

This package contains building blocks to create a completely flexible data table.
More specifically, it helps generate forms (with hidden inputs) and links that retain the state of your UX table.

The pros of this method:

- Full control over your template
- No javascript (even works with javascript disabled)
- No serialization

## Prerequisites

### Symfony UX Turbo

Make sure Symfony UX Turbo is installed and setup. We heavily rely on this functionality to make this a nice user experience.

## Install

```sh
composer require wedevelopnl/ux-table
```

## With Symfony Flex

You should be able to just get started.

## Without Symfony Flex

1. `npm i -D vendor/wedevelopnl/ux-table/assets`
2. Add to `bundles.php`
   ```php
   WeDevelop\UXTable\WeDevelopUXTableBundle::class => ['all' => true],
   ```
3. Add to `assets/controllers.json`
   ```json
   {
       "controllers": {
           "@wedevelopnl/ux-table": {
               "ux-table": {
                   "enabled": true,
                   "fetch": "eager"
               }
           }
       },
       "entrypoints": []
   }
   ```

## Getting started

### Create a form

First we create a new Form which extends `WeDevelop\UXTable\Table\AbstractTable`

```php
<?php

namespace App\UXTable;

use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WeDevelop\UXTable\Table\AbstractTable;

final class ProjectsTable extends AbstractTable
{
    public function getName(): string
    {
        return 'projects';
    }

    protected function buildFilterForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', SearchType::class, [
                'attr' => $this->stimulusSearchAttributes(),
                'required' => false,
            ])
        ;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => \App\Entity\Project::class,
            'sortable_fields' => ['name'],
        ]);
    }
}

```

This is a basic data table which adds a filter for the `name` field.

### Create a controller action

```php
    #[Route('/projects', name: 'app_project_list')]
    public function listAction(Request $request, ProjectsTable $projectsTable): Response
    {
        $projectsTable->process($request);

        return $this->render(
            'project/list.html.twig',
            ['projectsTable' => $projectsTable]
        );
    }
```

### Template

```twig
{# Optional optimization #}
{% extends app.request.headers.has('turbo-frame') ? 'empty.html.twig' : 'page.html.twig' %}

{% block main %}
    <h2>Projects</h2>

    <twig:UXTable:Table :table="projectsTable">
        <table>
            <thead>
            <tr>
                <th>
                    <twig:UXTable:SortLink :table="projectsTable" title="Name" field="name" />
                    <twig:UXTable:Filter :table="projectsTable" filter="name" />
                </th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            {% for project in projectsTable.results %}
                <tr>
                    <td>{{ project.name }}</td>
                    <td><a href="{{ path('app_project_view', {projectId: project.id}) }}" data-turbo-frame="_top">View</a>
                    </td>
                </tr>
            {% endfor %}
            </tbody>
        </table>

        Page size
        {{ form_widget(projectsTable.formView.pageSize) }}

        Pagination
        {{ knp_pagination_render(projectsTable.results) }}
    </twig:UXTable:Table>
{% endblock %}
```

There's a few important things here.

```twig
{% extends app.request.headers.has('turbo-frame') ? 'empty.html.twig' : 'page.html.twig' %}
```

This makes it so that when we're navigating within a Turbo Frame, we make sure not to render the entire layout, for
performance's sake.

```twig
<twig:UXTable:Table :table="projectsTable">
```

We use the UX Table Twig Component , it's a very slim component that makes sure everything is wrapped in a
stimulus controller, turbo frame and form tags

```twig
<twig:UXTable:SortLink :table="projectsTable" title="Name" field="name" />
```

Here we utilize the SortLink Twig Component to generate a link that retains the query parameters that contain the state
of the UX Table.

```twig
<twig:UXTable:Filter :table="projectsTable" filter="name" />
```

Here we utilize the Filter Twig Component to show the form field for that filter.

### Data Providers

By default, this package relies on the DoctrineORMProvider provided to automatically query the database.

If you want to use custom hydration you can configure a hydrator for the DoctrineORMProvider:

```php
protected function configureOptions(OptionsResolver $resolver): void
{
    parent::configureOptions($resolver);

    $resolver->setDefaults([
        'data_class' => \App\Entity\Project::class,
        'hydrator' => function (array $project) {
            return new \App\ReadModel\Project($project['id'], $project['name']);
        },
        'sortable_fields' => ['name'],
    ]);
}
```

You can also create your own DataProvider by creating a class that implements
the `WeDevelop\UXTable\DataProvider\DataProviderInterface`.

```php
final class ProjectsProvider implements DataProviderInterface
{
    public function __construct(
        private readonly ApiClient $api,
        private readonly PaginatorInterface $paginator,
    ) {
    }

    public function search(
        array $filters = [],
        array $sort = [],
        int $page = 1,
        int $pageSize = 50,
        array $options = []
    ): PaginationInterface {
        $status = $options['status'];

        $projects = $this->api->getProjects($status, $filters, $sort, $page, $pageSize);

        return $this->paginator->paginate($projects, $page, $pageSize, [PaginatorInterface::PAGE_OUT_OF_RANGE => PaginatorInterface::PAGE_OUT_OF_RANGE_THROW_EXCEPTION]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('status')->allowedTypes(ProjectStatus::class)->required()
        ;
    }
}
```

Here we also define a status option which can be passes to the process function:

```php
$projectsTable->process($request, options: ['status' => 'active']);
```
