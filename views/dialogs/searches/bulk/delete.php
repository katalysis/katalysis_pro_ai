<?php defined('C5_EXECUTE') or die("Access Denied."); ?>

<div class="ccm-ui">

    <?php if (count($searches) == 0) { ?>
        <div class="alert-message info">
            <?php echo t("No searches are eligible for this operation"); ?>
        </div>
    <?php } else { ?>
        
        <p><?php echo t('Are you sure you would like to delete the following searches?'); ?></p>

        <form method="post" data-dialog-form="delete-searches" action="<?php echo $controller->action('submit'); ?>">
            <?php
            foreach ($searches as $search) {
                ?>
                <input type="hidden" name="item[]" value="<?php echo $search->getId(); ?>"/>
            <?php
            } ?>

            <div class="ccm-ui">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo t('ID'); ?></th>
                            <th><?php echo t('Query'); ?></th>
                            <th><?php echo t('Started'); ?></th>
                            <th><?php echo t('Page'); ?></th>
                            <th><?php echo t('LLM'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($searches as $search) { ?>
                            <tr>
                                <td><?php echo $search->getId(); ?></td>
                                <td><?php echo h($search->getTruncatedQuery(50)); ?></td>
                                <td><?php echo $search->getStarted() ? $search->getStarted()->format('Y-m-d H:i:s') : t('N/A'); ?></td>
                                <td><?php echo h($search->getLaunchPageTitle() ?: 'N/A'); ?></td>
                                <td><?php echo h($search->getLlm()); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="dialog-buttons">
                <button class="btn btn-secondary" data-dialog-action="cancel"><?php echo t('Cancel'); ?></button>
                <button type="button" data-dialog-action="submit" class="btn btn-danger ms-auto"><?php echo t('Delete'); ?></button>
            </div>
        </form>

    <?php } ?>

</div>
