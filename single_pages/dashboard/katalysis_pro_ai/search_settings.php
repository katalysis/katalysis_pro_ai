<?php defined('C5_EXECUTE') or die("Access Denied.");

$ih = Core::make('helper/concrete/ui');
$form = Core::make('helper/form');

?>

<div class="ccm-dashboard-content-full">
    <div class="container-fluid">

        <form method="post" action="<?php echo $this->action('save'); ?>">
            <?php Core::make("token")->output('save_search_settings'); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><?php echo t('Search Settings') ?></h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="form-group">
                                <?php echo $form->label('search_result_prompt', t('Search Result Generation Prompt')) ?>
                                <?php echo $form->textarea('search_result_prompt', $searchResultPrompt, [
                                    'rows' => 6,
                                    'class' => 'form-control',
                                    'placeholder' => t('Instructions for how the AI should format search results...')
                                ]) ?>
                                <small class="form-text text-muted">
                                    <?php echo t('Define how the AI should generate search results. Available placeholders: {search_query}, {site_name}') ?>
                                </small>
                            </div>

                            <div class="form-group">
                                <?php echo $form->label('max_results', t('Maximum Results Per Search')) ?>
                                <?php echo $form->number('max_results', $maxResults, [
                                    'min' => 1,
                                    'max' => 20,
                                    'class' => 'form-control'
                                ]) ?>
                                <small class="form-text text-muted">
                                    <?php echo t('Maximum number of pages to include in search results (1-20)') ?>
                                </small>
                            </div>

                            <div class="form-group">
                                <?php echo $form->label('result_length', t('Result Length')) ?>
                                <?php echo $form->select('result_length', [
                                    'short' => t('Short - Brief summaries'),
                                    'medium' => t('Medium - Detailed descriptions'),
                                    'long' => t('Long - Comprehensive analysis')
                                ], $resultLength, ['class' => 'form-control']) ?>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <?php echo $form->checkbox('include_page_links', 1, $includePageLinks, ['class' => 'form-check-input']) ?>
                                    <?php echo $form->label('include_page_links', t('Include direct page links in results'), ['class' => 'form-check-label']) ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <?php echo $form->checkbox('show_snippets', 1, $showSnippets, ['class' => 'form-check-input']) ?>
                                    <?php echo $form->label('show_snippets', t('Show content snippets from pages'), ['class' => 'form-check-label']) ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><?php echo t('Specialist Recommendations') ?></h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="form-group">
                                <div class="form-check">
                                    <?php echo $form->checkbox('enable_specialists', 1, $enableSpecialists, ['class' => 'form-check-input']) ?>
                                    <?php echo $form->label('enable_specialists', t('Show specialist recommendations in sidebar'), ['class' => 'form-check-label']) ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <?php echo $form->label('specialists_prompt', t('Specialist Selection Prompt')) ?>
                                <?php echo $form->textarea('specialists_prompt', $specialistsPrompt, [
                                    'rows' => 4,
                                    'class' => 'form-control',
                                    'placeholder' => t('Instructions for how to identify relevant specialists...')
                                ]) ?>
                                <small class="form-text text-muted">
                                    <?php echo t('Define how the AI should identify and recommend specialists based on search queries') ?>
                                </small>
                            </div>

                            <div class="form-group">
                                <?php echo $form->label('max_specialists', t('Maximum Specialists to Show')) ?>
                                <?php echo $form->number('max_specialists', $maxSpecialists, [
                                    'min' => 1,
                                    'max' => 10,
                                    'class' => 'form-control'
                                ]) ?>
                            </div>

                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><?php echo t('Reviews Integration') ?></h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="form-group">
                                <div class="form-check">
                                    <?php echo $form->checkbox('enable_reviews', 1, $enableReviews, ['class' => 'form-check-input']) ?>
                                    <?php echo $form->label('enable_reviews', t('Show relevant reviews in sidebar'), ['class' => 'form-check-label']) ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <?php echo $form->label('reviews_prompt', t('Review Selection Prompt')) ?>
                                <?php echo $form->textarea('reviews_prompt', $reviewsPrompt, [
                                    'rows' => 4,
                                    'class' => 'form-control',
                                    'placeholder' => t('Instructions for selecting relevant reviews...')
                                ]) ?>
                                <small class="form-text text-muted">
                                    <?php echo t('Define how the AI should identify and display relevant Katalysis Pro reviews') ?>
                                </small>
                            </div>

                            <div class="form-group">
                                <?php echo $form->label('max_reviews', t('Maximum Reviews to Show')) ?>
                                <?php echo $form->number('max_reviews', $maxReviews, [
                                    'min' => 1,
                                    'max' => 10,
                                    'class' => 'form-control'
                                ]) ?>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><?php echo t('Search Statistics') ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong><?php echo t('Total Searches Today:') ?></strong><br>
                                <span class="badge badge-primary"><?php echo $searchesToday ?></span>
                            </div>
                            <div class="mb-3">
                                <strong><?php echo t('Total Searches This Month:') ?></strong><br>
                                <span class="badge badge-info"><?php echo $searchesThisMonth ?></span>
                            </div>
                            <div class="mb-3">
                                <strong><?php echo t('Most Popular Search Terms:') ?></strong><br>
                                <?php if (!empty($popularTerms)): ?>
                                    <ul class="list-unstyled">
                                        <?php foreach ($popularTerms as $term => $count): ?>
                                            <li><span class="badge badge-light"><?php echo h($term) ?></span> (<?php echo $count ?>)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <em><?php echo t('No search data available yet') ?></em>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><?php echo t('Help & Tips') ?></h5>
                        </div>
                        <div class="card-body">
                            <h6><?php echo t('Search Result Prompts') ?></h6>
                            <p><small><?php echo t('Use clear, specific instructions for how search results should be formatted and presented to users.') ?></small></p>
                            
                            <h6><?php echo t('Specialist Recommendations') ?></h6>
                            <p><small><?php echo t('Configure how the AI identifies team members or experts relevant to search topics.') ?></small></p>
                            
                            <h6><?php echo t('Review Integration') ?></h6>
                            <p><small><?php echo t('Set up automatic display of relevant customer reviews and testimonials alongside search results.') ?></small></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <?php echo t('Save Settings') ?>
                </button>
                <a href="<?php echo \Concrete\Core\Support\Facade\Url::to('/dashboard/katalysis_pro_ai') ?>" class="btn btn-secondary">
                    <?php echo t('Cancel') ?>
                </a>
            </div>

        </form>

    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.badge {
    font-size: 0.875em;
}

.form-group {
    margin-bottom: 1rem;
}
</style>
