<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="form-group">
    <label class="control-label form-label"><?php echo t('Display Mode') ?></label>
    <select name="displayMode" class="form-control">
        <option value="inline" <?php echo $displayMode === 'inline' ? 'selected' : '' ?>><?php echo t('Inline Results') ?></option>
        <option value="redirect" <?php echo $displayMode === 'redirect' ? 'selected' : '' ?>><?php echo t('Redirect to Results Page') ?></option>
    </select>
</div>

<div class="form-group" id="results-page-selector" style="<?php echo $displayMode === 'redirect' ? '' : 'display: none;' ?>">
    <label class="control-label form-label"><?php echo t('Results Page') ?></label>
    <select name="resultsPageId" class="form-control">
        <option value="0"><?php echo t('Select a page...') ?></option>
        <?php foreach ($pages as $pageId => $pageName): ?>
            <option value="<?php echo $pageId ?>" <?php echo $resultsPageId == $pageId ? 'selected' : '' ?>>
                <?php echo h($pageName) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label class="control-label form-label"><?php echo t('Search Placeholder Text') ?></label>
    <input type="text" name="searchPlaceholder" class="form-control" 
           value="<?php echo h($searchPlaceholder) ?>" 
           placeholder="<?php echo t('Search our knowledge base...') ?>">
</div>

<div class="form-group">
    <label class="control-label form-label"><?php echo t('Search Button Text') ?></label>
    <input type="text" name="searchButtonText" class="form-control" 
           value="<?php echo h($searchButtonText) ?>" 
           placeholder="<?php echo t('Search') ?>">
</div>

<div class="form-group">
    <label class="control-label form-label"><?php echo t('Maximum Results') ?></label>
    <input type="number" name="maxResults" class="form-control" 
           value="<?php echo $maxResults ?>" min="1" max="20">
</div>

<fieldset>
    <legend><?php echo t('Display Options') ?></legend>
    
    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="showSpecialists" name="showSpecialists" value="1" 
               <?php echo $showSpecialists ? 'checked' : '' ?>>
        <label class="form-check-label" for="showSpecialists">
            <?php echo t('Show Relevant Specialists') ?>
        </label>
    </div>

    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="showReviews" name="showReviews" value="1" 
               <?php echo $showReviews ? 'checked' : '' ?>>
        <label class="form-check-label" for="showReviews">
            <?php echo t('Show Relevant Reviews') ?>
        </label>
    </div>

    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="showPlaces" name="showPlaces" value="1" 
               <?php echo $showPlaces ? 'checked' : '' ?>>
        <label class="form-check-label" for="showPlaces">
            <?php echo t('Show Office Locations') ?>
        </label>
    </div>

    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="enableDebug" name="enableDebug" value="1" 
               <?php echo $enableDebug ? 'checked' : '' ?>>
        <label class="form-check-label" for="enableDebug">
            <?php echo t('Enable Debug Mode') ?>
        </label>
    </div>
</fieldset>

<script>
$(document).ready(function() {
    $('select[name="displayMode"]').on('change', function() {
        if ($(this).val() === 'redirect') {
            $('#results-page-selector').show();
        } else {
            $('#results-page-selector').hide();
        }
    });
});
</script>
