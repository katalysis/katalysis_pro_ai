<?php

defined('C5_EXECUTE') or die('Access denied');

\Log::info("actions.php");

/** @noinspection DuplicatedCode */

use Concrete\Core\Application\UserInterface\ContextMenu\MenuInterface;
use Concrete\Core\Support\Facade\Url;
use KatalysisProAi\Entity\Action;    
use KatalysisProAi\Search\Actions\Result\Column;
use KatalysisProAi\Search\Actions\Result\Item;
use KatalysisProAi\Search\Actions\Result\ItemColumn;
use KatalysisProAi\Search\Actions\Result\Result;
use KatalysisProAi\Search\Actions\Result\Menu;
use KatalysisProAi\ActionsMenu;

/** @var string|null $class */
/** @var MenuInterface $menu */
/** @var Result $result */

?>

<div id="ccm-search-results-table">
    <table class="ccm-search-results-table" data-search-results="actions">
        <thead>
            <tr>
                <th class="ccm-search-results-bulk-selector">
                    <div class="btn-group dropdown">
                        <span class="btn btn-secondary" data-search-checkbox-button="select-all">
                        <!--suppress HtmlFormInputWithoutLabel -->
                        <input type="checkbox" data-search-checkbox="select-all"/>
                    </span>
                    
                    <button
                        type="button"
                        disabled="disabled"
                        data-search-checkbox-button="dropdown"
                        class="btn btn-secondary dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown"
                        data-reference="parent">
                        
                        <span class="sr-only">
                            <?php echo t("Toggle Dropdown"); ?>
                        </span>
                    </button>
                </div>
            </th>
            <?php if(isset($result)) { ?>
                <?php foreach ($result->getColumns() as $column): ?>
                    <?php /** @var Column $column */ ?>
                    <th class="<?php echo $column->getColumnStyleClass() ?>">
                        <?php if ($column->isColumnSortable()): ?>
                            <a href="<?php echo $column->getColumnSortURL() ?>">
                                <?php echo $column->getColumnTitle() ?>
                            </a>
                        <?php else: ?>
                            <span>
                                <?php echo $column->getColumnTitle() ?>
                            </span>
                        <?php endif; ?>
                    </th>
                <?php endforeach; ?>
            <?php } ?>
            </tr>
        </thead>
        
        <tbody>
            <?php if(isset($result)) { ?>
                <?php foreach ($result->getItems() as $item) { ?>
                    <?php
                        /** @var Item $item */
                        /** @var Action $action */
                        $action = $item->getItem();
                    ?>
                    <tr data-details-url="javascript:void(0)">
                        <td class="ccm-search-results-checkbox">
                            <?php if ($action instanceof Action) { ?>
                                <!--suppress HtmlFormInputWithoutLabel -->
                                <input data-search-checkbox="individual"
                                    type="checkbox"
                                    data-item-id="<?php echo $action->getId() ?>"/>
                                <?php } ?>
                            </td>
                        
                        <?php foreach ($item->getColumns() as $column) { ?>
                            <?php /** @var ItemColumn $column */ ?>
                            <td class="<?php echo isset($class) ? $class : '' ?>">
                                <?php echo $column->getColumnValue(); ?>
                            </td>
                        <?php } ?>
                        
                        <?php $menu = new ActionsMenu($action); ?>
                        
                        <?php if ($menu) { ?>
                            <td class="ccm-search-results-menu-launcher">
                            <div class="dropdown" data-menu="search-result">
                            
                            <button class="btn btn-icon"
                                data-boundary="viewport"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                                
                                <svg width="16" height="4">
                                    <use xlink:href="#icon-menu-launcher"/>
                                </svg>
                            </button>
                            
                            <?php echo $menu->getMenuElement(); ?>
                        </div>
                    </td>
                <?php } ?>
            </tr>
        <?php } ?>
    <?php } ?>

</tbody>
</table>
</div>

<script>
    (function ($) {
        $(function () {
            let searchResultsTable = new window.ConcreteSearchResultsTable($("#ccm-search-results-table"));
            searchResultsTable.setupBulkActions();
            
            $('#ccm-search-results-table').on('click', 'a[data-id]', function () {
                window.location.href = '<?=rtrim(URL::to('/dashboard/katalysis_pro_ai/actions', 'edit'), '/')?>/' + $(this).attr('data-id');
                return false;
            });
            
        });
    })(jQuery);
</script>

<?php if(isset($result)) { ?>
<?php echo $result->getPagination()->renderView('dashboard'); ?>
<?php } ?> 