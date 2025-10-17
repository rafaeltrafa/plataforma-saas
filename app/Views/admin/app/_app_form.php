<form id="app-create-form" action="<?php echo base_url('admin/apps'); ?>" method="post">
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="app-name" class="form-label">Nome do App</label>
            <input type="text" class="form-control" id="app-name" name="name" placeholder="Ex: Meu Aplicativo" required>
        </div>
        <div class="col-md-12 mb-3">
            <label for="app-description" class="form-label">Descrição</label>
            <textarea class="form-control" id="app-description" name="description" rows="3" placeholder="Descreva o app"></textarea>
        </div>
        <div class="col-md-12">
            <small class="text-muted d-block mb-3">
                O <code>slug</code> e o <code>app_key</code> serão gerados automaticamente.
                O campo <code>is_active</code> será salvo como <strong>1</strong> por padrão.
            </small>
        </div>
    </div>
    <div class="d-flex justify-content-between">

        <button type="submit" class="btn bg-primary">Cadastrar App</button>
    </div>
</form>