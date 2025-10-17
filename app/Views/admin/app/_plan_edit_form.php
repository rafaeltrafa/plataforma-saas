<?php

/** @var int $appId */ /** @var array $plan */ ?>
<form id="plan-edit-form" action="<?= base_url('admin/apps/' . $appId . '/plans/' . (int)($plan['id'] ?? 0)) ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="plan_id" value="<?= (int)($plan['id'] ?? 0) ?>">
    <input type="hidden" name="is_active" value="<?= (int)($plan['is_active'] ?? 1) ?>">

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="plan-name" class="form-label">Nome do Plano</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ti ti-file-text text-primary fs-5"></i>
                    </span>
                    <input type="text" class="form-control" id="plan-name" name="name" value="<?= esc($plan['name'] ?? '') ?>" required>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="plan-billing" class="form-label">Cobrança</label>
                <?php $billing = $plan['billing_interval'] ?? ''; ?>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ti ti-calendar text-primary fs-5"></i>
                    </span>
                    <select class="form-select" id="plan-billing" name="billing_interval" required>
                        <option value="">Selecione...</option>
                        <option value="monthly" <?= $billing === 'monthly'   ? 'selected' : '' ?>>Mensal</option>
                        <option value="quarterly" <?= $billing === 'quarterly' ? 'selected' : '' ?>>Trimestral</option>
                        <option value="yearly" <?= $billing === 'yearly'    ? 'selected' : '' ?>>Anual</option>
                        <option value="one_time" <?= $billing === 'one_time'  ? 'selected' : '' ?>>Pagamento Único</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="plan-currency" class="form-label">Moeda</label>
                <?php $currency = strtoupper($plan['currency'] ?? ''); ?>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ti ti-currency-dollar text-primary fs-5"></i>
                    </span>
                    <select class="form-select" id="plan-currency" name="currency" required>
                        <option value="">Selecione...</option>
                        <option value="BRL" <?= $currency === 'BRL' ? 'selected' : '' ?>>Real (BRL)</option>
                        <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>Dólar (USD)</option>
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
                    <input type="text" inputmode="decimal" class="form-control" id="plan-price" name="price_amount" value="<?= esc($plan['price_amount'] ?? '') ?>" placeholder="Ex: 49,90" required>
                </div>
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
                    <input type="text" class="form-control" id="stripe-price-id" name="stripe_price_id" value="<?= esc($plan['stripe_price_id'] ?? '') ?>" placeholder="Ex: price_123abc">
                    <?php if (empty($plan['stripe_price_id'])) : ?>
                        <button type="button" class="btn btn-outline-primary generate-stripe-price-action">Gerar no Stripe</button>
                    <?php endif; ?>
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