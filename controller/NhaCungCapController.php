<?php
require_once __DIR__ . '/../core/Controller.php';

/**
 * Quản lý Nhà cung cấp (Admin / Nhân viên). Xóa chỉ dành cho Admin.
 * Dữ liệu form lấy thô từ $_POST và được escape khi hiển thị trong view.
 */
class NhaCungCapController extends Controller {
    private NhaCungCapModel $nhaCungCapModel;

    public function __construct() {
        $this->nhaCungCapModel = $this->model('NhaCungCapModel');
    }

    public function index(): void {
        $this->requireAdminAuth();
        $search = trim((string)($_GET['search'] ?? ''));

        $this->view('admin/nhacungcap/index', [
            'pageTitle' => 'Quản Lý Nhà Cung Cấp',
            'suppliers' => $this->nhaCungCapModel->listWithStats($search),
            'search'    => $search
        ]);
    }

    public function create(): void {
        $this->requireAdminAuth();
        $old = [];
        $errors = [];

        if ($this->isPost()) {
            $old = $_POST;
            $errors = $this->nhaCungCapModel->validate($_POST);
            if (empty($errors)) {
                $this->nhaCungCapModel->create($this->nhaCungCapModel->normalize($_POST));
                set_flash('ncc_success', 'Thêm nhà cung cấp thành công!', 'success');
                redirect('admin/nha-cung-cap');
                return;
            }
        }

        $this->view('admin/nhacungcap/create', [
            'pageTitle' => 'Thêm Nhà Cung Cấp',
            'old'       => $old,
            'errors'    => $errors
        ]);
    }

    public function edit($id): void {
        $this->requireAdminAuth();
        $supplier = $this->nhaCungCapModel->find((int)$id);
        if (!$supplier) {
            set_flash('ncc_error', 'Nhà cung cấp không tồn tại.', 'danger');
            redirect('admin/nha-cung-cap');
            return;
        }

        $errors = [];
        if ($this->isPost()) {
            $errors = $this->nhaCungCapModel->validate($_POST, (int)$id);
            if (empty($errors)) {
                $this->nhaCungCapModel->update((int)$id, $this->nhaCungCapModel->normalize($_POST));
                set_flash('ncc_success', 'Cập nhật nhà cung cấp thành công!', 'success');
                redirect('admin/nha-cung-cap');
                return;
            }
            $supplier = array_merge($supplier, $_POST);
        }

        $this->view('admin/nhacungcap/edit', [
            'pageTitle' => 'Chỉnh Sửa Nhà Cung Cấp',
            'supplier'  => $supplier,
            'errors'    => $errors
        ]);
    }

    public function delete($id): void {
        $this->requireAdminRole();
        $id = (int)$id;

        $supplier = $this->nhaCungCapModel->find($id);
        if (!$supplier) {
            set_flash('ncc_error', 'Nhà cung cấp không tồn tại.', 'danger');
        } else {
            $count = $this->nhaCungCapModel->countProducts($id);
            if ($count > 0) {
                set_flash('ncc_error', "Không thể xóa: còn {$count} sản phẩm thuộc nhà cung cấp này. Hãy chuyển các sản phẩm sang nhà cung cấp khác trước.", 'danger');
            } else {
                $this->nhaCungCapModel->delete($id);
                set_flash('ncc_success', 'Đã xóa nhà cung cấp.', 'success');
            }
        }
        redirect('admin/nha-cung-cap');
    }
}
