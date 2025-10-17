<?php $id = (int)($app['id'] ?? 0); ?>
<form id="app-edit-form" action="<?php echo base_url('admin/apps/' . $id); ?>" method="post">
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="edit-app-name" class="form-label">Nome do App</label>
            <input type="text" class="form-control" id="edit-app-name" name="name" value="<?php echo esc($app['name'] ?? ''); ?>" required>
        </div>
        <div class="col-md-12 mb-3">
            <label for="edit-app-description" class="form-label">Descrição</label>
            <textarea class="form-control" id="edit-app-description" name="description" rows="3" placeholder="Descreva o app"><?php echo esc($app['description'] ?? ''); ?></textarea>
        </div>
        <div class="col-md-12">
            <small class="text-muted d-block mb-3">
                O <code>slug</code> e o <code>app_key</code> existentes não serão alterados nesta edição.
            </small>
        </div>
    </div>
    <div class="d-flex justify-content-between">

        <button type="submit" class="btn bg-primary-subtle text-primary">Salvar Alterações</button>
    </div>
</form>