<?php
// handle active menu:
$activeMenu = $routeName;
$mainMenu = $view->menu('main');

if (is_null($mainMenu->get($routeName))) {
    $parts = explode('.', $routeName);
    $routeNames = [$parts[0], $parts[0].'.index'];
    foreach($routeNames as $rn) {
        if (!is_null($mainMenu->get($rn))) {
            $activeMenu = $rn;
            break;
        }
    }
}

$mainMenu->on($activeMenu, function($item, $menu) {
    
    $item->itemTag()->class('active');
    
    if ($item->getTreeLevel() > 0) {
        $item->parentTag()->class('active');
    }
    
    $item->tag()->class('active');
    
    return $item;
});

$mainMenu->sort(fn ($a, $b) => $a->text() <=> $b->text());
$mainMenu->sort(fn ($a, $b) => $b->getOrder() <=> $a->getOrder());

// show only active items tree:
$mainMenu->active($activeMenu);
$mainMenu->subitems(false);

// add classes for design:
$mainMenu->tag('ul')->level(0)->class('menu-v spaced menu-main');
?>
<div id="page-nav" class="page-nav">
    <div class="page-nav-off"><a href="#page-nav-off"><?= $view->etrans('close') ?></a></div>
    <?php if ($view->menu('main')->hasItems()) { ?>
        <nav id="menu-main"><?= $mainMenu ?></nav>
    <?php } ?>
</div>