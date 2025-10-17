<?php

/** @var int $appId */ ?>
<form id="plan-create-form" action="<?= base_url('admin/apps/' . $appId . '/plans') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="is_active" value="1">

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="plan-name" class="form-label">Nome do Plano</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ti ti-file-text text-primary fs-5"></i>
                    </span>
                    <input type="text" class="form-control" id="plan-name" name="name" placeholder="Ex: Plano Básico" required>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="plan-billing" class="form-label">Cobrança</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ti ti-calendar text-primary fs-5"></i>
                    </span>
                    <select class="form-select" id="plan-billing" name="billing_interval" required>
                        <option value="">Selecione...</option>
                        <option value="monthly">Mensal</option>
                        <option value="quarterly">Trimestral</option>
                        <option value="yearly">Anual</option>
                        <option value="one_time">Pagamento Único</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="plan-currency" class="form-label">Moeda</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ti ti-currency-dollar text-primary fs-5"></i>
                    </span>
                    <select class="form-select" id="plan-currency" name="currency" required>
                        <option value="">Selecione...</option>
                        <option value="BRL">BRL</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="plan-price" class="form-label">Preço</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ti ti-cash text-primary fs-5"></i>
                    </span>
                    <input type="text" class="form-control" id="plan-price" name="price_amount" placeholder="Ex: 49,90" required>
                </div>
                <small class="text-muted">O formato do preço depende da moeda selecionada.</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="stripe-price-id" class="form-label">Stripe Price ID</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ti ti-code text-primary fs-5"></i>
                    </span>
                    <input type="text" class="form-control" id="stripe-price-id" name="stripe_price_id" placeholder="Ex: price_123abc">
                </div>
                <small class="text-muted">Se vazio, será gerado automaticamente ao salvar.</small>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-center mt-3">
        <button type="button" id="cancel-plan-form" class="btn bg-danger-subtle text-danger">Voltar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
</form>