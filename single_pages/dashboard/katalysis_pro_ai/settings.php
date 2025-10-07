<?php
defined('C5_EXECUTE') or die('Access Denied.');

?>

<form method="post" enctype="multipart/form-data" action="<?= $controller->action('save') ?>">
    <?php $token->output('save_settings'); ?>
    <div id="ccm-dashboard-content-inner">
        <div class="row mb-5 justify-content-between">
            <div class="col-12 col-md-8 col-lg-6">
                <fieldset class="mb-5">
                    <legend><?php echo t('AI Settings'); ?></legend>
                    <div class="form-group">
                        <label class="form-label" for="open_ai_key"><?php echo t('Open AI Key'); ?></label>
                        <input class="form-control ccm-input-text" type="text" name="open_ai_key" id="open_ai_key"
                            value="<?= isset($open_ai_key) ? $open_ai_key : '' ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="open_ai_model"><?php echo t('Open AI Model'); ?></label>
                        <input class="form-control ccm-input-text" type="text" name="open_ai_model" id="open_ai_model"
                            value="<?= isset($open_ai_model) ? $open_ai_model : '' ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="anthropic_key"><?php echo t('Anthropic AI Key'); ?></label>
                        <input class="form-control ccm-input-text" type="text" name="anthropic_key" id="anthropic_key"
                            value="<?= isset($anthropic_key) ? $anthropic_key : '' ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="anthropic_model"><?php echo t('Anthropic AI Model'); ?></label>
                        <input class="form-control ccm-input-text" type="text" name="anthropic_model"
                            id="anthropic_model" value="<?= isset($anthropic_model) ? $anthropic_model : '' ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ollama_key"><?php echo t('Ollama Key'); ?></label>
                        <input class="form-control ccm-input-text" type="text" name="ollama_key" id="ollama_key"
                            value="<?= isset($ollama_key) ? $ollama_key : '' ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ollama_url"><?php echo t('Ollama URL'); ?></label>
                        <input class="form-control ccm-input-text" type="text" name="ollama_url" id="ollama_url"
                            value="<?= isset($ollama_url) ? $ollama_url : '' ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ollama_model"><?php echo t('Ollama Model'); ?></label>
                        <input class="form-control ccm-input-text" type="text" name="ollama_model" id="ollama_model"
                            value="<?= isset($ollama_model) ? $ollama_model : '' ?>" />
                    </div>
                </fieldset>

                <fieldset class="mb-5">
                    <legend><?php echo t('Typesense Settings'); ?></legend>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            // Toggle Typesense settings visibility
                            const typesenseCheckbox = document.getElementById('use_typesense');
                            const typesenseSettings = document.getElementById('typesense_settings');

                            if (typesenseCheckbox && typesenseSettings) {
                                typesenseCheckbox.addEventListener('change', function () {
                                    typesenseSettings.style.display = this.checked ? 'block' : 'none';
                                });
                            }
                        });

                        // Typesense connection test function
                        function testTypesenseConnection() {
                            const resultDiv = document.getElementById('connection-test-result');
                            const testButton = event.target;

                            // Show loading state
                            testButton.disabled = true;
                            testButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('Testing...'); ?>';
                            resultDiv.innerHTML = '<div class="alert alert-info"><?php echo t('Testing connection...'); ?></div>';

                            // Get form data
                            const formData = new FormData();
                            formData.append('typesense_api_key', document.getElementById('typesense_api_key').value);
                            formData.append('typesense_host', document.getElementById('typesense_host').value);
                            formData.append('typesense_port', document.getElementById('typesense_port').value);
                            formData.append('typesense_protocol', document.getElementById('typesense_protocol').value);

                            fetch('<?= $controller->action('test_typesense_connection') ?>', {
                                method: 'POST',
                                body: formData
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
                                    } else {
                                        resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                                    }
                                })
                                .catch(error => {
                                    resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('Connection test failed. Please check your settings.'); ?></div>';
                                })
                                .finally(() => {
                                    // Reset button state
                                    testButton.disabled = false;
                                    testButton.innerHTML = '<i class="fas fa-check-circle"></i> <?php echo t('Test Connection'); ?>';
                                });
                        }
                    </script>
                </fieldset>


                <fieldset class="mb-5">
                    <legend><?php echo t('Vector Storage Configuration') ?></legend>

                    <div class="alert alert-info mb-4">
                        <h6><i class="fas fa-cloud"></i> <?php echo t('Vector Storage Options') ?></h6>
                        <p class="mb-2">
                            <?php echo t('Choose between file-based storage (local) or Typesense Cloud (scalable, distributed).') ?>
                        </p>
                        <ul class="mb-0">
                            <li><strong><?php echo t('File Storage:') ?></strong>
                                <?php echo t('Default local storage, suitable for smaller deployments') ?></li>
                            <li><strong><?php echo t('Typesense Cloud:') ?></strong>
                                <?php echo t('Scalable cloud storage with advanced search capabilities') ?></li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <?php echo $form->checkbox('use_typesense', 1, $useTypesense, ['class' => 'form-check-input', 'id' => 'use_typesense']) ?>
                            <?php echo $form->label('use_typesense', t('Enable Typesense Cloud Storage'), ['class' => 'form-check-label']) ?>
                        </div>
                        <small class="form-text text-muted">
                            <?php echo t('When enabled, vector data will be stored in Typesense Cloud instead of local files') ?>
                        </small>
                    </div>

                    <div id="typesense_settings" style="<?php echo $useTypesense ? '' : 'display: none;'; ?>">
                        <div class="form-group">
                            <?php echo $form->label('typesense_api_key', t('Typesense API Key')) ?>
                            <?php echo $form->password('typesense_api_key', $typesenseApiKey, [
                                'class' => 'form-control',
                                'placeholder' => t('Enter your Typesense API key')
                            ]) ?>
                            <small class="form-text text-muted">
                                <?php echo t('Your Typesense Cloud API key for authentication') ?>
                            </small>
                        </div>

                        <div class="form-group">
                            <?php echo $form->label('typesense_host', t('Typesense Host')) ?>
                            <?php echo $form->text('typesense_host', $typesenseHost, [
                                'class' => 'form-control',
                                'placeholder' => t('e.g., xyz.a1.typesense.net')
                            ]) ?>
                            <small class="form-text text-muted">
                                <?php echo t('Your Typesense Cloud host URL (without protocol)') ?>
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <?php echo $form->label('typesense_port', t('Port')) ?>
                                    <?php echo $form->number('typesense_port', $typesensePort, [
                                        'class' => 'form-control',
                                        'min' => 1,
                                        'max' => 65535
                                    ]) ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <?php echo $form->label('typesense_protocol', t('Protocol')) ?>
                                    <?php echo $form->select('typesense_protocol', [
                                        'https' => 'HTTPS',
                                        'http' => 'HTTP'
                                    ], $typesenseProtocol, ['class' => 'form-control']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php echo $form->label('typesense_collection_prefix', t('Collection Prefix')) ?>
                            <?php echo $form->text('typesense_collection_prefix', $typesenseCollectionPrefix, [
                                'class' => 'form-control',
                                'placeholder' => t('katalysis_')
                            ]) ?>
                            <small class="form-text text-muted">
                                <?php echo t('Prefix for Typesense collection names (helps organize multiple sites)') ?>
                            </small>
                        </div>

                        <div class="form-group">
                            <button type="button" class="btn btn-outline-info btn-sm"
                                onclick="testTypesenseConnection()">
                                <i class="fas fa-check-circle"></i> <?php echo t('Test Connection') ?>
                            </button>
                            <div id="connection-test-result" class="mt-2"></div>
                        </div>
                    </div>
                </fieldset>
            </div>

            <div class="col-12 col-md-8 col-lg-5" style="max-width:500px;">
                <div class="alert alert-primary mb-5">
                    <h5><i class="fas fa-search"></i> <?php echo t('To be updated'); ?></h5>
                    <p class="mb-3">
                        <?php echo t('To be updated'); ?>
                    </p>
                </div>

                <fieldset class="mb-5">
                    <legend><?php echo t('Search Results Configuration'); ?></legend>
                </fieldset>
            </div>
        </div>

    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <div class="float-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save" aria-hidden="true"></i> <?php echo t('Save Settings'); ?>
                </button>
            </div>
        </div>
    </div>
</form>

