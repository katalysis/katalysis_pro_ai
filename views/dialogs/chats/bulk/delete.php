<?php defined('C5_EXECUTE') or die("Access Denied."); ?>

<div class="ccm-ui">

    <?php if (count($chats) == 0) { ?>
        <div class="alert-message info">
            <?php echo t("No chats are eligible for this operation"); ?>
        </div>
    <?php } else { ?>
        
        <p><?php echo t('Are you sure you would like to delete the following chats?'); ?></p>

        <form method="post" data-dialog-form="delete-chats" action="<?php echo $controller->action('submit'); ?>">
            <?php
            foreach ($chats as $chat) {
                ?>
                <input type="hidden" name="item[]" value="<?php echo $chat->getId(); ?>"/>
            <?php
            } ?>

            <div class="ccm-ui">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo t('ID'); ?></th>
                            <th><?php echo t('Started'); ?></th>
                            <th><?php echo t('Location'); ?></th>
                            <th><?php echo t('LLM'); ?></th>
                            <th><?php echo t('Created By'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chats as $chat) { ?>
                            <tr>
                                <td><?php echo $chat->getId(); ?></td>
                                <td><?php echo $chat->getStarted() ? $chat->getStarted()->format('Y-m-d H:i:s') : t('N/A'); ?></td>
                                <td><?php echo h($chat->getLocation()); ?></td>
                                <td><?php echo h($chat->getLlm()); ?></td>
                                <td><?php echo h($chat->getCreatedBy()); ?></td>
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