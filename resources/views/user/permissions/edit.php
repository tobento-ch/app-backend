<?php
$permissionsMenu = $view->menu('permissions');

foreach($areas as $area => $rules) {
    $permissionsMenu->html('<span class="pb-s">'.$view->etrans(':name area', [':name' => $area]).'</span>')->id($area);
    foreach($rules as $rule) {
        if (!str_contains($rule->getKey(), '.')) {
            $permissionsMenu->link('#'.$rule->getKey(), ucfirst($rule->getKey()))->parent($area);
        }
    }
}

$permissionsMenu->tag('ul')->level(0)->class('menu-v spaced menu-main');
?>
<!DOCTYPE html>
<html lang="<?= $view->esc($view->get('htmlLang', 'en')) ?>" class="scroll-behavior-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $view->esc($title) ?></title>
        <meta name="description" content="<?= $view->esc($title) ?>">
        <?= $view->render('inc/head') ?>
        <?= $view->assets()->render() ?>
    </head>
    <body<?= $view->tagAttributes('body')->add('class', 'page-asided')->render() ?>>

        <?= $view->render('inc/header') ?>
        <?= $view->render('inc/nav') ?>
        
        <aside class="page-aside">
        	<nav><?= $permissionsMenu ?></nav>
        </aside>
        
        <main class="page-main">

            <?= $view->render('inc.breadcrumb') ?>
            <?= $view->render('inc.messages') ?>

            <h1 class="text-xl"><?= $view->esc($title) ?></h1>
            
            <?php $form = $view->form(); ?>
            
            <?= $form->form(['action' => $formAction, 'method' => 'PUT']) ?>
            
            <div class="sticky-controls buttons spaced pt-xs mb-xs">
                <a href="<?= $view->esc($cancelUrl) ?>" class="button text-xs"><?= $view->etrans('Cancel') ?></a>
                <button class="button text-xs primary"><?= $view->etrans('Save') ?></button>
            </div>
            
            <div class="sections">
                <div class="section">
                    <h2 class="section-title"><?= $view->etrans('General') ?></h2>

                    <div class="field field-h">
                        <div class="field-label">
                            <?= $form->label(
                                text: $view->trans('Apply user rights'),
                                for: 'apply_permissions',
                                requiredText: '',
                            ) ?>
                        </div>
                        <div class="field-body">
                            <?= $form->radios(
                                name: 'apply_permissions',
                                items: ['0' => $view->trans('No'), '1' => $view->trans('Yes')],
                                selected: $user->setting('apply_permissions', '0'),
                                attributes: [],
                                labelAttributes: [],
                                withInput: true,
                                wrapClass: 'wrap-h'
                            ) ?>
                            <p class="text-xxs mt-xs"><?= $view->etrans('If "Yes", the specific user rights get applied, otherwise the role rights.') ?></p>
                        </div>
                    </div>
                </div>
                <?php foreach($areas as $area => $rules) { ?>
                    <div class="section">
                        <h2 class="section-title"><?= $view->etrans(':name area', [':name' => $area]) ?></h2>
                        <?php foreach(array_values($rules) as $key => $rule) { ?>
                            <a class="fragment" id="<?= $view->esc($rule->getKey()) ?>"></a>
                            <?php $class = str_contains($rule->getKey(), '.') ? ' pl-m' : ''; ?>
                            <div class="cols mb-s<?= $class ?>">
                                <div class="pr-s">
                                    <?= $form->input(
                                        name: 'permissions.'.$key,
                                        type: 'checkbox',
                                        value: $rule->getKey(),
                                        selected: in_array($rule->getKey(), $userPermissions) ? $rule->getKey() : null,
                                    ) ?>
                                </div>
                                <div>
                                    <?= $form->label(
                                        text: $rule->getTitle()
                                            ? $view->trans($rule->getTitle()).' ['.$rule->getKey().']'
                                            : '['.$rule->getKey().']',
                                        for: 'permissions.'.$key,
                                        attributes: ['class' => 'title'],
                                    ) ?>
                                    <?php if ($rule->getDescription()) { ?>
                                        <p class="pt-xxs"><?= $view->etrans($rule->getDescription()) ?></p>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
            
            <?= $form->close() ?>
        </main>

        <?= $view->render('inc/footer') ?>
    </body>
</html>