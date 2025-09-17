<?php defined('C5_EXECUTE') or die("Access Denied.");

$form = Core::make('helper/form');

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
            'placeholder' => t('Search our knowledge base...')
        ]) ?>
    </div>

    <div class="form-group">
        <?php echo $form->label('searchButtonText', t('Search Button Text')) ?>
        <?php echo $form->text('searchButtonText', $searchButtonText, [
            'class' => 'form-control',
            'placeholder' => t('Search')
        ]) ?>
    </div>

    <div class="form-group">
        <?php echo $form->label('maxResults', t('Maximum Results to Display')) ?>
        <?php echo $form->number('maxResults', $maxResults, [
            'min' => 1,
            'max' => 20,
            'class' => 'form-control'
        ]) ?>
        <small class="form-text text-muted"><?php echo t('Number of search results to show (1-20)') ?></small>
    </div>

    <div class="form-group">
        <div class="form-check">
            <?php echo $form->checkbox('showSpecialists', 1, $showSpecialists, ['class' => 'form-check-input']) ?>
            <?php echo $form->label('showSpecialists', t('Show specialist recommendations'), ['class' => 'form-check-label']) ?>
        </div>
        <small class="form-text text-muted"><?php echo t('Display relevant team members or specialists in the sidebar') ?></small>
    </div>

    <div class="form-group">
        <div class="form-check">
            <?php echo $form->checkbox('showReviews', 1, $showReviews, ['class' => 'form-check-input']) ?>
            <?php echo $form->label('showReviews', t('Show relevant reviews'), ['class' => 'form-check-label']) ?>
        </div>
        <small class="form-text text-muted"><?php echo t('Display relevant customer reviews and testimonials') ?></small>
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

.ccm-ui .form-check {
    margin-bottom: 0.5rem;
}

.ccm-ui .form-text {
    font-size: 0.875rem;
    color: #6c757d;
}
</style>
