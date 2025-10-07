<?php defined('C5_EXECUTE') or die("Access Denied.");

$form = \Concrete\Core\Support\Facade\Application::getFacadeApplication()->make('helper/form');

?>

<div class="ccm-ui">

    <div class="form-group">
        <?php echo $form->label('displayMode', t('Display Mode')) ?>
        <?php echo $form->select('displayMode', [
            'inline' => t('Show results below search box'),
            'redirect' => t('Redirect to results page')
        ], $displayMode, ['class' => 'form-control']) ?>
        <small class="form-text text-muted"><?php echo t('Choose how search results should be displayed') ?></small>
    </div>

    <div class="form-group" id="results-page-group" style="<?php echo $displayMode === 'redirect' ? '' : 'display: none;' ?>">
        <?php echo $form->label('resultsPageId', t('Results Page')) ?>
        <?php echo $form->select('resultsPageId', $pages, $resultsPageId, ['class' => 'form-control']) ?>
        <small class="form-text text-muted"><?php echo t('Select the page where search results will be displayed') ?></small>
    </div>

    <div class="form-group">
        <?php echo $form->label('searchPlaceholder', t('Search Placeholder Text')) ?>
        <?php echo $form->text('searchPlaceholder', $searchPlaceholder, [
            'class' => 'form-control',
            'placeholder' => t('How can we help you today?')
        ]) ?>
        <small class="form-text text-muted"><?php echo t('Placeholder text shown in the search input field') ?></small>
    </div>

    <div class="form-group">
        <?php echo $form->label('searchButtonText', t('Search Button Text')) ?>
        <?php echo $form->text('searchButtonText', $searchButtonText, [
            'class' => 'form-control',
            'placeholder' => t('Search')
        ]) ?>
        <small class="form-text text-muted"><?php echo t('Text displayed on the search button') ?></small>
    </div>

    <div class="alert alert-info">
        <h5><?= t('Configuration Notes') ?></h5>
        <p><?= t('This block uses the AI search settings configured in Dashboard > Katalysis Pro AI > Search Settings. To modify response format, AI behavior, or integration settings, visit that page.') ?></p>
        <p>
            <strong><?= t('Current Settings:') ?></strong>
        </p>
        <ul>
            <li><?= t('OpenAI Integration: %s', \Concrete\Core\Support\Facade\Config::get('katalysis.ai.open_ai_key') ? t('Configured') : t('Not Configured')) ?></li>
            <li><?= t('Typesense Search: %s', \Concrete\Core\Support\Facade\Config::get('katalysis.search.typesense_host') ? t('Configured') : t('Not Configured')) ?></li>
            <li><?= t('Specialists: %s (Max: %d)', \Concrete\Core\Support\Facade\Config::get('katalysis.search.enable_specialists', true) ? t('Enabled') : t('Disabled'), \Concrete\Core\Support\Facade\Config::get('katalysis.search.max_specialists', 3)) ?></li>
            <li><?= t('Reviews: %s (Max: %d)', \Concrete\Core\Support\Facade\Config::get('katalysis.search.enable_reviews', true) ? t('Enabled') : t('Disabled'), \Concrete\Core\Support\Facade\Config::get('katalysis.search.max_reviews', 3)) ?></li>
            <li><?= t('Places: %s (Max: %d)', \Concrete\Core\Support\Facade\Config::get('katalysis.search.enable_places', true) ? t('Enabled') : t('Disabled'), \Concrete\Core\Support\Facade\Config::get('katalysis.search.max_places', 3)) ?></li>
            <li><?= t('AI Context Documents: %d per category', max(2, intval(\Concrete\Core\Support\Facade\Config::get('katalysis.search.candidate_documents_count', 15) / 5))) ?></li>
            <li><?= t('Case Studies/Articles: %d per search', \Concrete\Core\Support\Facade\Config::get('katalysis.search.max_articles_case_studies', 4)) ?></li>
        </ul>
    </div>

</div>

<script>
$(document).ready(function() {
    $('#displayMode').on('change', function() {
        if ($(this).val() === 'redirect') {
            $('#results-page-group').show();
        } else {
            $('#results-page-group').hide();
        }
    });
});
</script>

<style>
.ccm-ui .form-group {
    margin-bottom: 1rem;
}

.ccm-ui .form-text {
    font-size: 0.875rem;
    color: #6c757d;
}
</style>
