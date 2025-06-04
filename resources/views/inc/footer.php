<?php
// handle active menu:
$view->menu('footer')->on($routeName, function($item, $menu) {
    
    $item->itemTag()->class('active');
    
    if ($item->getTreeLevel() > 0) {
        $item->parentTag()->class('active');
    }
    
    if ($item instanceof \Tobento\Service\Tag\Taggable) {
        $item->tag()->class('active');
    }
    
    if ($item instanceof Tobento\Service\Menu\Link) {
        $item = $item->withUrl('#');
    }
    
    return $item;
});

// sort menu by its order:
$view->menu('footer')
    //->subitems(false)
    ->sort(fn ($a, $b) => $b->getOrder() <=> $a->getOrder());

// add classes for design:
$view->menu('footer')->tag('ul')->level(0)->class('menu-h spaced menu-side');
$view->menu('footer')->tag('ul')->level(1)->class('dropup');
$view->menu('footer')->tag('li')->level(0)->class('link');
?>
<footer class="page-footer">
    <?php if ($view->menu('footer')->hasItems()) { ?>
        <nav><?= $view->menu('footer') ?></nav>
    <?php } ?>
</footer>