<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/AiClient.php';

class TuVanApiController extends Controller {
    private TuVanModel $tuVanModel;
    private SanPhamModel $sanPhamModel;
    private AiClient $aiClient;

    public function __construct() {
        $this->tuVanModel = $this->model('TuVanModel');
        $this->sanPhamModel = $this->model('SanPhamModel');
        $this->aiClient = new AiClient();
    }

    /**
     * POST /api/v1/phien-tu-van
     * Tạo hoặc lấy phiên tư vấn hiện tại (hỗ trợ force_new: true để làm mới)
     */
    public function createSession(): void {
        $body = $this->getBody();
        $forceNew = !empty($body['force_new']) || $this->getQuery('force_new') === '1';

        $userId = $_SESSION['user']['id'] ?? null;
        $sessionId = $userId ? null : $this->getCartSessionId();

        $session = $this->tuVanModel->getOrCreateSession($userId, $sessionId, $forceNew);
        $messages = $forceNew ? [] : $this->tuVanModel->getMessagesBySessionId((int)$session['id']);

        $this->created([
            'session'  => $session,
            'messages' => $messages
        ], 'Khởi tạo phiên tư vấn thành công.', base_url('api/v1/phien-tu-van/' . $session['id']));
    }

    /**
     * GET /api/v1/phien-tu-van/{id}/tin-nhan
     * Lấy lịch sử hội thoại của 1 phiên tư vấn
     */
    public function getMessages($sessionId): void {
        $sessionId = (int)$sessionId;
        $session = $this->tuVanModel->find($sessionId);

        if (!$session) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Phiên tư vấn không tồn tại.'], 404);
            return;
        }

        $messages = $this->tuVanModel->getMessagesBySessionId($sessionId);

        $this->json([
            'success' => true,
            'data'    => [
                'session'  => $session,
                'messages' => $messages
            ],
            'message' => 'Lấy lịch sử tin nhắn thành công.'
        ], 200);
    }

    /**
     * POST /api/v1/phien-tu-van/{id}/tin-nhan
     * Gửi tin nhắn mới -> Backend gọi AI -> Trả về câu trả lời và sản phẩm gợi ý
     */
    public function sendMessage($sessionId): void {
        $sessionId = (int)$sessionId;
        $session = $this->tuVanModel->find($sessionId);

        if (!$session) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Phiên tư vấn không tồn tại.'], 404);
            return;
        }

        $body = $this->getBody();
        $messageText = trim($body['noi_dung'] ?? $body['message'] ?? '');

        do {
            if (empty($messageText)) {
                $this->json(['success' => false, 'data' => null, 'message' => 'Nội dung tin nhắn không được để trống.'], 400);
                break;
            }

            // 1. Lưu tin nhắn của khách vào CSDL
            $userMsgId = $this->tuVanModel->addMessage($sessionId, 'khach', $messageText);

            // 2. Tìm kiếm sản phẩm liên quan từ CSDL để nạp ngữ cảnh cho AI (RAG)
            $matchedProducts = $this->tuVanModel->findRelevantProducts($messageText, 5);

            // 3. Lấy lịch sử hội thoại gần nhất
            $history = $this->tuVanModel->getMessagesBySessionId($sessionId);

            // 4. Gọi AI Assistant xử lý
            $aiResult = $this->aiClient->chat($messageText, $history, $matchedProducts);
            $aiReplyText = $aiResult['text'];
            $suggestedProductId = $aiResult['suggested_product_id'];

            // 5. Lưu tin nhắn trả lời của AI vào CSDL
            $aiMsgId = $this->tuVanModel->addMessage($sessionId, 'ai', $aiReplyText, $suggestedProductId);

            // 6. Lấy chi tiết sản phẩm gợi ý nếu có
            $suggestedProductData = null;
            if ($suggestedProductId) {
                $p = $this->sanPhamModel->find($suggestedProductId);
                if ($p) {
                    $price = $p['gia_khuyen_mai'] > 0 ? $p['gia_khuyen_mai'] : $p['gia'];
                    $suggestedProductData = [
                        'id'               => (int)$p['id'],
                        'ten_san_pham'     => $p['ten_san_pham'],
                        'gia'              => (float)$p['gia'],
                        'gia_khuyen_mai'   => (float)$p['gia_khuyen_mai'],
                        'gia_dinh_dang'    => format_currency($price),
                        'hinh_anh_url'     => !empty($p['hinh_anh']) ? asset_url($p['hinh_anh']) : asset_url('assets/images/default.jpg'),
                        'detail_url'       => base_url('san-pham/' . ($p['slug'] ?? $p['id'])),
                        'chat_lieu'        => $p['chat_lieu'] ?? '',
                        'kich_thuoc'       => $p['kich_thuoc'] ?? '',
                        'mau_sac'          => $p['mau_sac'] ?? ''
                    ];
                }
            }

            $this->created([
                'user_message' => [
                    'id'        => $userMsgId,
                    'nguoi_gui' => 'khach',
                    'noi_dung'  => $messageText,
                    'thoi_gian' => date('Y-m-d H:i:s')
                ],
                'ai_response' => [
                    'id'               => $aiMsgId,
                    'nguoi_gui'        => 'ai',
                    'noi_dung'         => $aiReplyText,
                    'san_pham_goi_y'   => $suggestedProductData,
                    'thoi_gian'        => date('Y-m-d H:i:s')
                ]
            ], 'Gửi tin nhắn thành công.');
            return;
        } while (false);
    }

    /**
     * GET /api/v1/phien-tu-van
     * Danh sách phiên tư vấn cho Admin theo dõi chất lượng
     */
    public function index(): void {
        $this->requireAdminAuth();

        $page = max(1, (int)$this->getQuery('page', 1));
        $limit = max(1, min(100, (int)$this->getQuery('limit', 20)));
        $offset = ($page - 1) * $limit;

        $sessions = $this->tuVanModel->getSessionList($limit, $offset);
        $total = $this->tuVanModel->countSessions();

        $this->paginate($sessions, $total, $page, $limit, 'Lấy danh sách phiên tư vấn thành công.');
    }

    /**
     * DELETE /api/v1/phien-tu-van/{id}
     * Xóa phiên tư vấn (Admin)
     */
    public function deleteSession($sessionId): void {
        $this->requireAdminAuth();
        $sessionId = (int)$sessionId;

        $session = $this->tuVanModel->find($sessionId);
        if (!$session) {
            $this->json(['success' => false, 'data' => null, 'message' => 'Phiên tư vấn không tồn tại.'], 404);
            return;
        }

        $deleted = $this->tuVanModel->delete($sessionId);
        if ($deleted) {
            $this->json([
                'success' => true,
                'data'    => ['id' => $sessionId],
                'message' => 'Đã xóa phiên tư vấn thành công.'
            ], 200);
            return;
        }

        $this->json(['success' => false, 'data' => null, 'message' => 'Không thể xóa phiên tư vấn.'], 500);
    }
}
