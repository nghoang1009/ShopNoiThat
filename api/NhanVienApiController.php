<?php
require_once __DIR__ . '/../core/Controller.php';

class NhanVienApiController extends Controller {
    private NhanVienModel $nhanVienModel;

    public function __construct() {
        $this->nhanVienModel = $this->model('NhanVienModel');
    }

    /**
     * GET /api/v1/nhan-vien
     * Danh sách nhân viên
     */
    public function index(): void {
        $this->requireAdminAuth();

        $employees = $this->nhanVienModel->all('id DESC');
        foreach ($employees as &$emp) {
            unset($emp['mat_khau']);
        }

        $this->json([
            'success' => true,
            'data'    => $employees,
            'message' => 'Lấy danh sách nhân viên thành công.'
        ], 200);
    }

    /**
     * GET /api/v1/nhan-vien/{id}
     * Chi tiết nhân viên
     */
    public function detail($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;

        $employee = $this->nhanVienModel->find($id);
        if (!$employee) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Không tìm thấy nhân viên.'], 404);
            return;
        }

        unset($employee['mat_khau']);
        $this->json([
            'success' => true,
            'data'    => $employee,
            'message' => 'Lấy chi tiết nhân viên thành công.'
        ], 200);
    }

    /**
     * POST /api/v1/nhan-vien
     * Tạo mới tài khoản nhân viên (Admin)
     */
    public function create(): void {
        $this->requireAdminAuth();
        $body = $this->getBody();

        $rules = [
            'ho_ten'    => ['required' => true, 'min' => 2, 'label' => 'Họ và tên'],
            'tai_khoan' => ['required' => true, 'min' => 3, 'label' => 'Tên đăng nhập'],
            'email'     => ['required' => true, 'email' => true, 'label' => 'Email'],
            'mat_khau'  => ['required' => true, 'min' => 6, 'label' => 'Mật khẩu']
        ];

        do {
            $errors = $this->validateInputs($rules, $body);

            if (empty($errors['tai_khoan'])) {
                $existUsername = $this->nhanVienModel->findByUsername(trim($body['tai_khoan']));
                if ($existUsername) {
                    $errors['tai_khoan'] = 'Tên tài khoản này đã tồn tại.';
                }
            }

            if (empty($errors['email'])) {
                $existEmail = $this->nhanVienModel->findByEmail(trim($body['email']));
                if ($existEmail) {
                    $errors['email'] = 'Địa chỉ email này đã tồn tại.';
                }
            }

            if (!empty($errors)) {
                $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 400);
                break;
            }

            $data = [
                'ho_ten'     => trim($body['ho_ten']),
                'tai_khoan'  => trim($body['tai_khoan']),
                'email'      => trim($body['email']),
                'sdt'        => trim($body['sdt'] ?? ''),
                'mat_khau'   => password_hash($body['mat_khau'], PASSWORD_BCRYPT),
                'vai_tro'    => in_array($body['vai_tro'] ?? '', ['admin', 'nhan_vien']) ? $body['vai_tro'] : 'nhan_vien',
                'trang_thai' => isset($body['trang_thai']) ? (int)$body['trang_thai'] : 1
            ];

            $id = $this->nhanVienModel->create($data);
            if ($id) {
                $created = $this->nhanVienModel->find($id);
                unset($created['mat_khau']);
                $this->created($created, 'Tạo tài khoản nhân viên thành công.', base_url('api/v1/nhan-vien/' . $id));
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể tạo nhân viên.'], 500);
            return;
        } while (false);
    }

    /**
     * PUT /api/v1/nhan-vien/{id}
     * Cập nhật nhân viên
     */
    public function update($id): void {
        $this->requireAdminAuth();
        $id = (int)$id;
        $body = $this->getBody();

        do {
            $employee = $this->nhanVienModel->find($id);
            if (!$employee) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Nhân viên không tồn tại.'], 404);
                break;
            }

            $rules = [
                'ho_ten' => ['required' => true, 'min' => 2, 'label' => 'Họ và tên'],
                'email'  => ['required' => true, 'email' => true, 'label' => 'Email']
            ];

            $errors = $this->validateInputs($rules, $body);
            if (!empty($errors)) {
                $this->json(['success' => false, 'data' => null, 'errors' => $errors, 'message' => reset($errors)], 400);
                break;
            }

            $data = [
                'ho_ten'     => trim($body['ho_ten']),
                'email'      => trim($body['email']),
                'sdt'        => trim($body['sdt'] ?? ''),
                'vai_tro'    => in_array($body['vai_tro'] ?? '', ['admin', 'nhan_vien']) ? $body['vai_tro'] : 'nhan_vien',
                'trang_thai' => isset($body['trang_thai']) ? (int)$body['trang_thai'] : 1
            ];

            if (!empty($body['mat_khau'])) {
                $data['mat_khau'] = password_hash($body['mat_khau'], PASSWORD_BCRYPT);
            }

            $this->nhanVienModel->update($id, $data);
            $updated = $this->nhanVienModel->find($id);
            unset($updated['mat_khau']);

            $this->json([
                'success' => true,
                'data'    => $updated,
                'message' => 'Cập nhật nhân viên thành công.'
            ], 200);
            return;
        } while (false);
    }

    /**
     * DELETE /api/v1/nhan-vien/{id}
     * Xóa nhân viên
     */
    public function delete($id): void {
        $admin = $this->requireAdminAuth();
        $id = (int)$id;

        do {
            if ($id === (int)$admin['id']) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Không thể xóa chính tài khoản đang đăng nhập.'], 400);
                break;
            }

            $employee = $this->nhanVienModel->find($id);
            if (!$employee) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Nhân viên không tồn tại.'], 404);
                break;
            }

            $deleted = $this->nhanVienModel->delete($id);
            if ($deleted) {
                $this->json([
                    'success' => true,
                    'data'    => ['id' => $id],
                    'message' => 'Đã xóa nhân viên thành công.'
                ], 200);
                return;
            }

            $this->json(['success' => false, 'data' => null, 'message' => 'Không thể xóa nhân viên.'], 500);
            return;
        } while (false);
    }
}
