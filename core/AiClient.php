<?php
/**
 * Core AI Client - Quản lý kết nối và điều phối yêu cầu tới AI Assistant (Claude, OpenAI, Gemini)
 * Chạy 100% phía Server, bảo mật API Key, hỗ trợ RAG ngữ cảnh sản phẩm nội thất.
 */
class AiClient {
    private string $provider;
    private string $apiKey;
    private string $model;
    private int $timeout;

    public function __construct() {
        $this->provider = defined('AI_PROVIDER') ? AI_PROVIDER : 'claude';
        $this->apiKey   = defined('AI_API_KEY') ? AI_API_KEY : '';
        $this->model    = defined('AI_MODEL') ? AI_MODEL : 'claude-3-5-sonnet-20241022';
        $this->timeout  = defined('AI_TIMEOUT') ? AI_TIMEOUT : 15;
    }

    /**
     * Gửi hội thoại tới AI kèm ngữ cảnh sản phẩm nội thất thực tế từ CSDL
     *
     * @param string $userMessage Câu hỏi của khách hàng
     * @param array $history Lịch sử các tin nhắn gần nhất
     * @param array $matchedProducts Danh sách sản phẩm có thật trong kho liên quan đến câu hỏi
     * @return array ['text' => string, 'suggested_product_id' => ?int]
     */
    public function chat(string $userMessage, array $history = [], array $matchedProducts = []): array {
        // 1. Chuẩn bị ngữ cảnh sản phẩm (Context Injection / RAG)
        $productContext = $this->buildProductContext($matchedProducts);

        // 2. System Prompt chuyên biệt cho chuyên gia tư vấn nội thất
        $systemPrompt = "Bạn là Chuyên gia Tư vấn Thiết kế và Bày trí Nội thất cao cấp của cửa hàng 'Shop Nội Thất Luxury'.
Nhiệm vụ của bạn:
1. Lắng nghe nhu cầu, sở thích, diện tích phòng, ngân sách của khách hàng để đưa ra lời khuyên thẩm mỹ, cách phối màu, chất liệu và phong cách (Scandinavian, Tối giản, Hiện đại, Hoàng gia...).
2. QUY TẮC BẮT BUỘC: Bạn CHỈ ĐƯỢC PHÉP gợi ý các sản phẩm CÓ THẬT trong danh sách sau đây được cung cấp từ kho hàng. TUYỆT ĐỐI KHÔNG tự bịa ra sản phẩm, mã hàng hoặc mức giá không có trong danh sách:
{$productContext}
3. Khi bạn thấy một sản phẩm trong danh sách trên rất phù hợp và muốn gợi ý cho khách hàng, hãy phân tích lý do chọn và đặt cú pháp đặc biệt này ở cuối câu trả lời: [PRODUCT_ID: {id của sản phẩm}] (ví dụ: [PRODUCT_ID: 1]).
4. Giữ giọng điệu lịch sự, chuyên nghiệp, tận tâm, súc tích và ấm áp bằng tiếng Việt.";

        // Nếu có cấu hình API Key thật thì gọi External AI API
        if (!empty($this->apiKey)) {
            $response = $this->callExternalApi($systemPrompt, $userMessage, $history);
            if ($response !== null) {
                return $this->parseAiResponse($response, $matchedProducts);
            }
        }

        // Fallback: Engine tư vấn nội thất thông minh nội bộ (khi chưa cấu hình API key hoặc cURL timeout)
        return $this->fallbackInteriorAdvisor($userMessage, $matchedProducts);
    }

    /**
     * Gọi API bên ngoài (Anthropic Claude / OpenAI) qua cURL
     */
    private function callExternalApi(string $systemPrompt, string $userMessage, array $history): ?string {
        if ($this->provider === 'claude') {
            return $this->callAnthropicClaude($systemPrompt, $userMessage, $history);
        } elseif ($this->provider === 'openai') {
            return $this->callOpenAi($systemPrompt, $userMessage, $history);
        }
        return null;
    }

    /**
     * Gọi Anthropic Messages API
     */
    private function callAnthropicClaude(string $systemPrompt, string $userMessage, array $history): ?string {
        $messages = [];
        foreach ($history as $h) {
            $role = ($h['nguoi_gui'] === 'khach') ? 'user' : 'assistant';
            $messages[] = [
                'role'    => $role,
                'content' => $h['noi_dung']
            ];
        }
        $messages[] = [
            'role'    => 'user',
            'content' => $userMessage
        ];

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 1024,
            'system'     => $systemPrompt,
            'messages'   => $messages
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $result) {
            $json = json_decode($result, true);
            if (!empty($json['content'][0]['text'])) {
                return $json['content'][0]['text'];
            }
        }
        return null;
    }

    /**
     * Gọi OpenAI Chat Completions API
     */
    private function callOpenAi(string $systemPrompt, string $userMessage, array $history): ?string {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        foreach ($history as $h) {
            $role = ($h['nguoi_gui'] === 'khach') ? 'user' : 'assistant';
            $messages[] = [
                'role'    => $role,
                'content' => $h['noi_dung']
            ];
        }
        $messages[] = [
            'role'    => 'user',
            'content' => $userMessage
        ];

        $payload = [
            'model'       => $this->model ?: 'gpt-4o-mini',
            'messages'    => $messages,
            'temperature' => 0.7
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $result) {
            $json = json_decode($result, true);
            if (!empty($json['choices'][0]['message']['content'])) {
                return $json['choices'][0]['message']['content'];
            }
        }
        return null;
    }

    /**
     * Trích xuất ID sản phẩm gợi ý và làm sạch phản hồi
     */
    private function parseAiResponse(string $rawText, array $matchedProducts): array {
        $suggestedId = null;
        if (preg_match('/\[PRODUCT_ID:\s*(\d+)\]/i', $rawText, $matches)) {
            $candidateId = (int)$matches[1];
            // Xác thực ID có thực sự nằm trong danh sách sản phẩm hay không
            foreach ($matchedProducts as $p) {
                if ((int)$p['id'] === $candidateId) {
                    $suggestedId = $candidateId;
                    break;
                }
            }
            // Xóa thẻ tag khỏi text hiển thị
            $rawText = trim(preg_replace('/\[PRODUCT_ID:\s*\d+\]/i', '', $rawText));
        }

        // Nếu chưa bắt được qua tag nhưng có 1 sản phẩm khớp cao nhất
        if (!$suggestedId && !empty($matchedProducts)) {
            $suggestedId = (int)$matchedProducts[0]['id'];
        }

        return [
            'text'                 => $rawText,
            'suggested_product_id' => $suggestedId
        ];
    }

    /**
     * Chuỗi hóa danh sách sản phẩm thực tế để đưa vào Prompt Context
     */
    private function buildProductContext(array $products): string {
        if (empty($products)) {
            return "Hiện tại không có sản phẩm nào phù hợp trực tiếp với từ khóa tìm kiếm trong kho.";
        }

        $lines = [];
        foreach ($products as $p) {
            $price = $p['gia_khuyen_mai'] > 0 ? $p['gia_khuyen_mai'] : $p['gia'];
            $priceVnd = format_currency($price);
            $stock = $p['so_luong_ton'] > 0 ? "Còn hàng ({$p['so_luong_ton']} cái)" : "Tạm hết hàng";

            $lines[] = "- [ID: {$p['id']}] {$p['ten_san_pham']} (Danh mục: {$p['ten_danh_muc']}) | Giá: {$priceVnd} | Chất liệu: {$p['chat_lieu']} | Màu: {$p['mau_sac']} | Kích thước: {$p['kich_thuoc']} | Tình trạng: {$stock}";
        }
        return implode("\n", $lines);
    }

    /**
     * Chọn sản phẩm phù hợp nhất trong danh sách sản phẩm khớp theo từ khóa mục tiêu
     */
    private function pickBestProduct(array $products, array $targetKeywords): ?array {
        if (empty($products)) return null;

        foreach ($products as $p) {
            $text = mb_strtolower($p['ten_san_pham'] . ' ' . ($p['ten_danh_muc'] ?? ''));
            foreach ($targetKeywords as $kw) {
                if (str_contains($text, mb_strtolower($kw))) {
                    return $p;
                }
            }
        }
        return $products[0];
    }

    /**
     * Bộ máy tư vấn chuyên sâu Offline (NLP Heuristic & Domain Knowledge)
     */
    private function fallbackInteriorAdvisor(string $message, array $matchedProducts): array {
        $msgLower = mb_strtolower(trim($message));

        // 1. Chào hỏi
        if (str_contains($msgLower, 'chào') || str_contains($msgLower, 'hello') || str_contains($msgLower, 'hi') || $msgLower === '') {
            return [
                'text' => "Dạ chào bạn! Em là AI Trợ lý Tư vấn Nội Thất Luxury. Em có thể hỗ trợ bạn tìm kiếm mẫu bàn ghế, sofa, giường ngủ, tủ kệ phù hợp với diện tích phòng và ngân sách. Bạn đang cần trang bị nội thất cho không gian nào ạ (Phòng khách, Phòng ngủ, Phòng ăn hay Phòng làm việc)?",
                'suggested_product_id' => !empty($matchedProducts) ? (int)$matchedProducts[0]['id'] : null
            ];
        }

        // 2. Bàn ăn & Phòng bếp (Ưu tiên kiểm tra trước)
        if (str_contains($msgLower, 'bàn ăn') || str_contains($msgLower, 'phòng ăn') || str_contains($msgLower, 'bếp') || (str_contains($msgLower, 'bàn') && !str_contains($msgLower, 'trà') && !str_contains($msgLower, 'làm việc'))) {
            $prod = $this->pickBestProduct($matchedProducts, ['bàn ăn', 'concorde', 'bàn']);
            if ($prod) {
                $price = $prod['gia_khuyen_mai'] > 0 ? $prod['gia_khuyen_mai'] : $prod['gia'];
                return [
                    'text' => "Khu vực bàn ăn nên ưu tiên chất liệu mặt đá Ceramic chống ố và chịu nhiệt tốt. Em xin gợi ý bộ **{$prod['ten_san_pham']}** giá chỉ **" . format_currency($price) . "** (chất liệu {$prod['chat_lieu']}), thiết kế tinh tế giúp bữa cơm gia đình thêm ấm áp và tiện nghi.",
                    'suggested_product_id' => (int)$prod['id']
                ];
            }
        }

        // 3. Bàn trà phòng khách
        if (str_contains($msgLower, 'bàn trà') || str_contains($msgLower, 'bàn tròn')) {
            $prod = $this->pickBestProduct($matchedProducts, ['bàn trà', 'tròn đôi', 'bàn']);
            if ($prod) {
                $price = $prod['gia_khuyen_mai'] > 0 ? $prod['gia_khuyen_mai'] : $prod['gia'];
                return [
                    'text' => "Cho phòng khách, mẫu **{$prod['ten_san_pham']}** chất liệu **{$prod['chat_lieu']}** với mức giá **" . format_currency($price) . "** sẽ là điểm nhấn hiện đại hoàn hảo bên cạnh bộ sofa của gia đình bạn.",
                    'suggested_product_id' => (int)$prod['id']
                ];
            }
        }

        // 4. Sofa phòng khách
        if (str_contains($msgLower, 'sofa') || str_contains($msgLower, 'phòng khách')) {
            $prod = $this->pickBestProduct($matchedProducts, ['sofa', 'milano', 'nordic']);
            if ($prod) {
                $price = $prod['gia_khuyen_mai'] > 0 ? $prod['gia_khuyen_mai'] : $prod['gia'];
                return [
                    'text' => "Đối với không gian phòng khách, em gợi ý mẫu **{$prod['ten_san_pham']}** làm từ **{$prod['chat_lieu']}** với tông màu **{$prod['mau_sac']}** rất trang nhã. Kích thước {$prod['kich_thuoc']}, giá ưu đãi chỉ **" . format_currency($price) . "** kèm bảo hành 5 năm.",
                    'suggested_product_id' => (int)$prod['id']
                ];
            }
        }

        // 5. Ghế văn phòng / Công thái học / Ghế thư giãn
        if (str_contains($msgLower, 'công thái học') || str_contains($msgLower, 'ergonomic') || str_contains($msgLower, 'ghế làm việc') || str_contains($msgLower, 'văn phòng') || str_contains($msgLower, 'ghế')) {
            $prod = $this->pickBestProduct($matchedProducts, ['sihoo', 'ergonomic', 'poang', 'ghế']);
            if ($prod) {
                $price = $prod['gia_khuyen_mai'] > 0 ? $prod['gia_khuyen_mai'] : $prod['gia'];
                return [
                    'text' => "Để làm việc thoải mái và bảo vệ cột sống, mẫu **{$prod['ten_san_pham']}** chất liệu **{$prod['chat_lieu']}** với giá **" . format_currency($price) . "** là sự lựa chọn hàng đầu chống đau mỏi vai gáy hiệu quả.",
                    'suggested_product_id' => (int)$prod['id']
                ];
            }
        }

        // 6. Giường ngủ & Phòng ngủ
        if (str_contains($msgLower, 'giường') || str_contains($msgLower, 'phòng ngủ') || str_contains($msgLower, 'ngủ')) {
            $prod = $this->pickBestProduct($matchedProducts, ['giường', 'tokyo', 'royal']);
            if ($prod) {
                $price = $prod['gia_khuyen_mai'] > 0 ? $prod['gia_khuyen_mai'] : $prod['gia'];
                return [
                    'text' => "Một giấc ngủ sâu cần chiếc giường chắc chắn và êm ái. Mẫu **{$prod['ten_san_pham']}** chất liệu **{$prod['chat_lieu']}** (kích thước {$prod['kich_thuoc']}) với mức giá **" . format_currency($price) . "** là mẫu giường bán chạy nhất bên em.",
                    'suggested_product_id' => (int)$prod['id']
                ];
            }
        }

        // 7. Tủ áo & Kệ tivi
        if (str_contains($msgLower, 'tủ') || str_contains($msgLower, 'kệ') || str_contains($msgLower, 'tivi')) {
            $prod = $this->pickBestProduct($matchedProducts, ['tủ', 'kệ', 'cánh kính']);
            if ($prod) {
                $price = $prod['gia_khuyen_mai'] > 0 ? $prod['gia_khuyen_mai'] : $prod['gia'];
                return [
                    'text' => "Mẫu **{$prod['ten_san_pham']}** chất liệu **{$prod['chat_lieu']}** với giá **" . format_currency($price) . "** mang lại không gian lưu trữ đồ rộng rãi và tối ưu diện tích ngôi nhà.",
                    'suggested_product_id' => (int)$prod['id']
                ];
            }
        }

        // 8. Đèn trang trí
        if (str_contains($msgLower, 'đèn') || str_contains($msgLower, 'chiếu sáng')) {
            $prod = $this->pickBestProduct($matchedProducts, ['đèn', 'chùm', 'sputnik', 'thả']);
            if ($prod) {
                $price = $prod['gia_khuyen_mai'] > 0 ? $prod['gia_khuyen_mai'] : $prod['gia'];
                return [
                    'text' => "Ánh sáng là linh hồn của không gian! Em gợi ý mẫu **{$prod['ten_san_pham']}** giá chỉ **" . format_currency($price) . "**, ánh sáng ấm cúng và sang trọng.",
                    'suggested_product_id' => (int)$prod['id']
                ];
            }
        }

        // 9. Mặc định
        $prod = !empty($matchedProducts) ? $matchedProducts[0] : null;
        if ($prod) {
            $price = $prod['gia_khuyen_mai'] > 0 ? $prod['gia_khuyen_mai'] : $prod['gia'];
            return [
                'text' => "Dạ theo yêu cầu của bạn, em tìm thấy mẫu **{$prod['ten_san_pham']}** rất đáng cân nhắc. Sản phẩm có chất liệu **{$prod['chat_lieu']}**, màu **{$prod['mau_sac']}**, giá ưu đãi **" . format_currency($price) . "**. Bạn có thể xem ảnh và thông số chi tiết bên dưới nhé!",
                'suggested_product_id' => (int)$prod['id']
            ];
        }

        return [
            'text' => "Cảm ơn bạn đã nhắn tin! Cửa hàng chúng em cung cấp đầy đủ các món đồ nội thất từ sofa, bàn ăn, giường ngủ, tủ áo đến đèn trang trí cao cấp. Bạn có thể cho em biết thêm chi tiết về món đồ bạn đang tìm kiếm không ạ?",
            'suggested_product_id' => null
        ];
    }
}
