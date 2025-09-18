<?php

defined('C5_EXECUTE') or die('Access denied.');

/** @var KatalysisProAi\Entity\Search $search */
/** @var string $pageTitle */

?>

<div class="ccm-dashboard-header-buttons">
    <a href="<?= \Concrete\Core\Support\Facade\Url::to('/dashboard/katalysis_pro_ai/searches') ?>" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i> <?= t('Back to Searches') ?>
    </a>
</div>

<div class="ccm-dashboard-content-full">
    <div class="container-fluid">
        <div class="row justify-content-between">
            <div class="col-md-5">
                <h4><?= t('Search Information') ?></h4>
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th style="width:40%;"><?= t('ID') ?></th>
                            <td><?= $search->getId() ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('Query') ?></th>
                            <td>
                                <div class="search-query-display bg-light p-2 rounded border">
                                    <i class="fa fa-search text-primary me-1"></i>
                                    <strong><?= h($search->getQuery()) ?></strong>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('Started') ?></th>
                            <td><?= $search->getDisplayStarted() ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('Created Date') ?></th>
                            <td><?= $search->getDisplayCreatedDate() ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('Location') ?></th>
                            <td><?= h($search->getLocation() ?: t('Not set')) ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('LLM') ?></th>
                            <td><?= h($search->getLlm() ?: t('Not set')) ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('Session ID') ?></th>
                            <td><?= h($search->getSessionId() ?: t('Not set')) ?></td>
                        </tr>
                    </tbody>
                </table>

                <h4><?= t('Page Information') ?></h4>
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th style="width:40%;"><?= t('Page Title') ?></th>
                            <td><?= h($search->getLaunchPageTitle() ?: t('Not set')) ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('Page URL') ?></th>
                            <td style="word-wrap: break-word;word-break: break-all;">
                                <?php if ($search->getLaunchPageUrl()): ?>
                                    <a href="<?= h($search->getLaunchPageUrl()) ?>" target="_blank">
                                        <?= h($search->getLaunchPageUrl()) ?>
                                    </a>
                                <?php else: ?>
                                    <?= t('Not set') ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('Page Type') ?></th>
                            <td><?= h($search->getLaunchPageType() ?: t('Not set')) ?></td>
                        </tr>
                    </tbody>
                </table>

                <h4><?= t('User Information') ?></h4>
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th style="width:40%;"><?= t('Name') ?></th>
                            <td><?= h($search->getName() ?: t('Not set')) ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('Email') ?></th>
                            <td>
                                <?php if ($search->getEmail()): ?>
                                    <a href="mailto:<?= h($search->getEmail()) ?>">
                                        <?= h($search->getEmail()) ?>
                                    </a>
                                <?php else: ?>
                                    <?= t('Not set') ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('Phone') ?></th>
                            <td><?= h($search->getPhone() ?: t('Not set')) ?></td>
                        </tr>
                    </tbody>
                </table>

                <h4><?= t('UTM Tracking') ?></h4>
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th style="width:40%;"><?= t('UTM ID') ?></th>
                            <td><?= h($search->getUtmId() ?: t('Not set')) ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('UTM Source') ?></th>
                            <td><?= h($search->getUtmSource() ?: t('Not set')) ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('UTM Medium') ?></th>
                            <td><?= h($search->getUtmMedium() ?: t('Not set')) ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('UTM Campaign') ?></th>
                            <td><?= h($search->getUtmCampaign() ?: t('Not set')) ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('UTM Term') ?></th>
                            <td><?= h($search->getUtmTerm() ?: t('Not set')) ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?= t('UTM Content') ?></th>
                            <td><?= h($search->getUtmContent() ?: t('Not set')) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-md-6">
                <div class="row mt-4">
                    <div class="col-12">
                        <h4><?= t('Search Results & Analysis') ?></h4>
                        
                        <!-- Search Query Display -->
                        <div class="search-query-visualization mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fa fa-search me-2"></i>
                                        <?= t('User Search Query') ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="search-query-bubble bg-light p-3 rounded-3 border-start border-primary border-4">
                                        <i class="fa fa-quote-left text-muted me-2"></i>
                                        <span class="search-query-text fw-bold"><?= h($search->getQuery()) ?></span>
                                        <i class="fa fa-quote-right text-muted ms-2"></i>
                                    </div>
                                    <div class="search-meta mt-2">
                                        <small class="text-muted">
                                            <i class="fa fa-clock me-1"></i>
                                            <?= t('Searched at %s', $search->getDisplayStarted()) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AI Response Summary -->
                        <?php if ($search->getResultSummary()): ?>
                        <div class="ai-response-visualization mb-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fa fa-robot me-2"></i>
                                        <?= t('AI Response Summary') ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="ai-response-content bg-success bg-opacity-10 p-3 rounded border-start border-success border-4">
                                        <?= nl2br(h($search->getResultSummary())) ?>
                                    </div>
                                    <div class="ai-meta mt-2">
                                        <small class="text-muted">
                                            <i class="fa fa-microchip me-1"></i>
                                            <?= t('Generated by %s', h($search->getLlm() ?: 'AI')) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Search Analytics -->
                        <div class="search-analytics-visualization mb-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fa fa-chart-line me-2"></i>
                                        <?= t('Search Analytics') ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="stat-box text-center p-2 bg-light rounded">
                                                <div class="stat-value h4 text-primary mb-1">
                                                    <?= strlen($search->getQuery()) ?>
                                                </div>
                                                <div class="stat-label small text-muted">
                                                    <?= t('Query Length') ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-box text-center p-2 bg-light rounded">
                                                <div class="stat-value h4 text-success mb-1">
                                                    <?= str_word_count($search->getQuery()) ?>
                                                </div>
                                                <div class="stat-label small text-muted">
                                                    <?= t('Word Count') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($search->getResultSummary()): ?>
                                    <div class="row mt-3">
                                        <div class="col-6">
                                            <div class="stat-box text-center p-2 bg-light rounded">
                                                <div class="stat-value h4 text-warning mb-1">
                                                    <?= strlen($search->getResultSummary()) ?>
                                                </div>
                                                <div class="stat-label small text-muted">
                                                    <?= t('Response Length') ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-box text-center p-2 bg-light rounded">
                                                <div class="stat-value h4 text-info mb-1">
                                                    <?= str_word_count($search->getResultSummary()) ?>
                                                </div>
                                                <div class="stat-label small text-muted">
                                                    <?= t('Response Words') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Search Context -->
                        <div class="search-context-visualization">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="fa fa-map-marker-alt me-2"></i>
                                        <?= t('Search Context') ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="context-timeline">
                                        <div class="context-item d-flex align-items-center mb-2">
                                            <div class="context-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px;">
                                                <i class="fa fa-globe fa-sm"></i>
                                            </div>
                                            <div class="context-content">
                                                <div class="context-title small fw-bold"><?= t('Page Context') ?></div>
                                                <div class="context-detail small text-muted">
                                                    <?= h($search->getLaunchPageTitle() ?: t('Unknown page')) ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($search->getLocation()): ?>
                                        <div class="context-item d-flex align-items-center mb-2">
                                            <div class="context-icon bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px;">
                                                <i class="fa fa-location-arrow fa-sm"></i>
                                            </div>
                                            <div class="context-content">
                                                <div class="context-title small fw-bold"><?= t('Location') ?></div>
                                                <div class="context-detail small text-muted">
                                                    <?= h($search->getLocation()) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($search->getSessionId()): ?>
                                        <div class="context-item d-flex align-items-center mb-2">
                                            <div class="context-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px;">
                                                <i class="fa fa-fingerprint fa-sm"></i>
                                            </div>
                                            <div class="context-content">
                                                <div class="context-title small fw-bold"><?= t('Session') ?></div>
                                                <div class="context-detail small text-muted">
                                                    <?= substr(h($search->getSessionId()), 0, 12) ?>...
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($search->getUtmSource() || $search->getUtmMedium() || $search->getUtmCampaign()): ?>
                                        <div class="context-item d-flex align-items-center">
                                            <div class="context-icon bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px;">
                                                <i class="fa fa-bullhorn fa-sm"></i>
                                            </div>
                                            <div class="context-content">
                                                <div class="context-title small fw-bold"><?= t('Marketing Source') ?></div>
                                                <div class="context-detail small text-muted">
                                                    <?php 
                                                    $utmParts = array_filter([
                                                        $search->getUtmSource(),
                                                        $search->getUtmMedium(),
                                                        $search->getUtmCampaign()
                                                    ]);
                                                    echo h(implode(' / ', $utmParts) ?: t('Direct'));
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($search->getPlaceholderMessage()): ?>
                        <!-- Placeholder Message -->
                        <div class="placeholder-message-visualization mt-4">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">
                                        <i class="fa fa-comment-dots me-2"></i>
                                        <?= t('Placeholder Message') ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="placeholder-content bg-secondary bg-opacity-10 p-3 rounded border-start border-secondary border-4">
                                        <?= nl2br(h($search->getPlaceholderMessage())) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.search-query-bubble {
    position: relative;
    font-size: 1.1em;
}

.search-query-text {
    color: #0d6efd;
}

.stat-box {
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.stat-box:hover {
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0,123,255,0.1);
}

.context-timeline {
    position: relative;
}

.context-icon {
    flex-shrink: 0;
}

.context-content {
    flex-grow: 1;
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.card-header {
    border-bottom: none;
    font-weight: 500;
}
</style>
