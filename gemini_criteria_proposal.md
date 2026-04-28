===
**gemini version**

# Bảng Tiêu Chí Đánh Giá Độ Hoàn Thiện Project Proposal (Proposal Evaluation Rubric)

Bảng đánh giá này dựa trên hướng dẫn chuẩn của môn học `COMP1682 - Project Proposal`, giúp sinh viên và người hướng dẫn (Supervisor) tự kiểm tra và đánh giá chất lượng của bản đề xuất dự án.

## 1. Bảng Tiêu Chí Chấm Điểm (Rubric)

|  STT   | Hạng mục (Section)                                        | Yêu cầu chi tiết (Dựa trên Best Practices)                                                                                                                                                                                                                                       | Trọng số | Trạng thái / Điểm |
| :----: | :-------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :------: | :---------------: |
| **1**  | **Giới thiệu (Introduction)**                             | - Ngắn gọn (1-2 đoạn/phần).<br>- Mở bài thu hút, liên kết với nhu cầu thực tế.<br>- Nêu rõ **tính mới (Originality)** của dự án.<br>- Không đi quá sâu vào chi tiết kỹ thuật ở phần này.                                                                                         |  **5%**  |        [ ]        |
| **2**  | **Phát biểu bài toán (Problem Statement)**                | - Tập trung vào **MỘT** vấn đề cốt lõi.<br>- Định nghĩa rõ ràng, dùng các thuật ngữ có thể đo lường.<br>- Nêu rõ **tầm quan trọng** (dùng số liệu/trích dẫn chứng minh).<br>- Nêu rõ bối cảnh và các giả định/ràng buộc (Context & Assumptions).                                 | **10%**  |        [ ]        |
| **3**  | **Mục đích & Mục tiêu (Aim & Objectives)**                | - **Aim:** 1 câu tổng quát (Xây dựng cái gì? Cho ai? Để làm gì?).<br>- **Objectives:** 3-5 bước hành động cụ thể.<br>- Bắt buộc viết theo chuẩn **SMART** (Bắt đầu bằng "To + Verb").<br>- ⚠️ _Tuyệt đối không đưa các công nghệ cụ thể (React, Laravel,...) vào Objectives._    | **15%**  |        [ ]        |
| **4**  | **Tổng quan tài liệu (Literature Review)**                | - Phân tích phản biện (Critical Analysis) thay vì chỉ tóm tắt.<br>- Trích dẫn 10-15 nguồn uy tín (Journals, Conferences).<br>- Đánh giá Điểm mạnh, Điểm yếu, Phương pháp của các giải pháp hiện có.<br>- **Bắt buộc:** Chỉ ra được **Lỗ hổng (Gap)** mà dự án này sẽ giải quyết. | **15%**  |        [ ]        |
| **5**  | **Phương pháp đề xuất (Methodology)**                     | - Lựa chọn phương pháp luận (Agile, Waterfall, CRISP-DM...) có cơ sở.<br>- Giải thích **LÝ DO** chọn các công nghệ/tools (Justification).<br>- Có kế hoạch quản lý dữ liệu và các bước phát triển cụ thể.                                                                        | **10%**  |        [ ]        |
| **6**  | **Phạm vi & Tính khả thi (Scope & Feasibility)**          | - Phân định rạch ròi giới hạn **In-Scope** (MVP) và **Out-of-Scope**.<br>- Đánh giá khả thi theo "Tam giác Feasibility": **Time** (Thời gian), **Technical** (Kỹ thuật), **Resources** (Tài nguyên/Dữ liệu).                                                                     | **10%**  |        [ ]        |
| **7**  | **Đánh giá & Thành công (Evaluation & Success Criteria)** | - Xác định rõ cách đo lường dự án (Testing, Metrics...).<br>- Các tiêu chí thành công phải cụ thể và **đo lường được** (VD: Accuracy > 85%, Độ trễ < 500ms).<br>- Tránh các câu mơ hồ như "Hệ thống sẽ chạy tốt".                                                                | **10%**  |        [ ]        |
| **8**  | **Kế hoạch & Tiến độ (Project Plan & Timeline)**          | - Có biểu đồ **Gantt Chart** minh họa các Tasks và Milestones.<br>- Bố trí hợp lý các dependency (việc gì làm trước/sau).<br>- Đã bao gồm thời gian dự phòng **Buffer time (10-15%)**.<br>- Đã phân bổ thời gian cho việc viết Báo cáo (Report Writing).                         | **10%**  |        [ ]        |
| **9**  | **Kết quả dự kiến (Expected Outcomes)**                   | - Liệt kê cụ thể các sản phẩm bàn giao (Deliverables): Software, Model, Documentation, Research...<br>- Sản phẩm đóng góp trực tiếp vào việc giải quyết "Gap" đã nêu ở phần 4.                                                                                                   |  **5%**  |        [ ]        |
| **10** | **LSEPI & Rủi ro (LSEPI & Risks)**                        | - Phân tích đủ 4 khía cạnh: **L**egal, **S**ocial, **E**thical, **P**rofessional.<br>- Có ma trận đánh giá rủi ro (Risk Assessment Matrix).<br>- Có **chiến lược giảm thiểu (Mitigation Strategy)** rõ ràng cho các rủi ro có tác động High/Critical.                            | **10%**  |        [ ]        |

---

## 2. Checklist Lỗi Thường Gặp (Red Flags)

_Nếu Proposal của bạn mắc phải bất kỳ lỗi nào dưới đây, hãy sửa lại ngay lập tức trước khi nộp:_

- [ ] **Lỗi 1:** Problem Statement quá chung chung (Vd: "Customer churn is a problem").
- [ ] **Lỗi 2:** Objectives không theo chuẩn SMART, hoặc bị gắn chặt với 1 ngôn ngữ lập trình cụ thể.
- [ ] **Lỗi 3:** Literature Review chỉ tóm tắt lại bài báo người khác mà không có sự phản biện (không so sánh ưu/nhược điểm).
- [ ] **Lỗi 4:** Chọn công nghệ (React, Laravel, Python...) nhưng không có lý do biện minh (No justification).
- [ ] **Lỗi 5:** Phạm vi dự án (Scope) quá tham vọng hoặc không rõ ràng dẫn đến "Scope creep".
- [ ] **Lỗi 6:** Timeline phi thực tế, nhồi nhét mọi thứ vào tuần cuối, không có buffer time.
- [ ] **Lỗi 7:** Tiêu chí thành công (Success criteria) không thể đo lường được (Vd: "User will like it").
- [ ] **Lỗi 8:** Bỏ qua hoặc viết rất hời hợt về các vấn đề LSEPI.
- [ ] **Lỗi 9:** Nêu rủi ro nhưng không đề xuất giải pháp phòng tránh/giảm thiểu.
- [ ] **Lỗi 10:** Sai ngữ pháp, đạo văn (Plagiarism), hoặc định dạng văn bản cẩu thả.

---

## 3. Lời khuyên cuối cùng (Final Tips)

> ✓ **Be Specific** - Tránh dùng ngôn từ mơ hồ.  
> ✓ **Be Realistic** - Biết rõ giới hạn khả năng của bản thân.  
> ✓ **Be Critical** - Luôn phân tích, đừng chỉ mô tả lại.  
> ✓ **Be Professional** - Rà soát lỗi chính tả, định dạng chuẩn chỉ.  
> ✓ **Be Original** - Làm nổi bật điểm độc đáo (Unique) trong dự án của bạn.
