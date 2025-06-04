<?php
// handle active menu item.
// We could do it also outside the view.

// handle active menu:
$view->menu('header')->on($routeName, function($item, $menu) {
    $item->itemTag()->class('active');
    
    if ($item->getTreeLevel() > 0) {
        $item->parentTag()->class('active');
    }
    
    $item->tag()->class('active');
    
    return $item;
});

// add menu link used for table and mobile:
if ($view->menu('header')->hasItems()) {
    $view->menu('header')
        ->link('#page-nav', 'Menu')
        ->icon('menu')
        ->order(10000)
        ->itemTag()
        ->class('menu-link');
}

// sort menu by its order:
$view->menu('header')
    //->subitems(false)
    ->sort(fn ($a, $b) => $b->getOrder() <=> $a->getOrder());

// add classes for design:
$view->menu('header')->tag('ul')->level(0)->class('menu-h spaced menu-side');
$view->menu('header')->tag('ul')->level(1)->class('dropdown');
$view->menu('header')->tag('li')->level(0)->class('link');
?>
<header class="page-header">
    <div class="cols middle spaceBetween">
        <?= $view->render('search.bar') ?>
        <?php if ($view->menu('header')->hasItems()) { ?>
            <nav><?= $view->menu('header') ?></nav>
        <?php } ?>
    </div>
</header>