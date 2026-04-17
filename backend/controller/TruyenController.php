<?php
require_once __DIR__ . '/../model/TruyenModel.php';

class TruyenController {
	private $truyenModel;

	public function __construct() {
		$this->truyenModel = new TruyenModel();
	}

	private function getCoverUploadDir() {
		$destDir = realpath(__DIR__ . '/../uploads/anhbia');
		if ($destDir === false) {
			$destDir = __DIR__ . '/../uploads/anhbia';
			if (!is_dir($destDir)) {
				@mkdir($destDir, 0755, true);
			}
			$destDir = realpath($destDir) ?: $destDir;
		}

		return $destDir;
	}

	private function normalizeCoverDbPath($coverPath) {
		$coverPath = trim((string)$coverPath);
		if ($coverPath === '') {
			return '';
		}

		if (preg_match('#^https?://#i', $coverPath)) {
			return $coverPath;
		}

		$normalized = ltrim(str_replace('\\', '/', $coverPath), '/');
		if (strpos($normalized, 'web_doc_truyen/') === 0) {
			$normalized = substr($normalized, strlen('web_doc_truyen/'));
		}

		$legacyPrefixes = [
			'uploads/anhbia/',
			'frontend/public/uploads/anhbia/',
			'frontend/images/anhbia/',
			'images/anhbia/',
			'backend/uploads/anhbia/'
		];

		foreach ($legacyPrefixes as $prefix) {
			if (strpos($normalized, $prefix) === 0) {
				$fileName = basename($normalized);
				if ($fileName !== '' && $fileName !== '.' && $fileName !== '..') {
					return 'uploads/anhbia/' . $fileName;
				}
				return '';
			}
		}

		$prefixes = [
			'backend/',
			'frontend/public/'
		];

		foreach ($prefixes as $prefix) {
			if (strpos($normalized, $prefix) === 0) {
				$normalized = substr($normalized, strlen($prefix));
				break;
			}
		}

		return $normalized;
	}

	private function deleteOldCoverFile($coverPath) {
		$normalized = $this->normalizeCoverDbPath($coverPath);
		if ($normalized === '' || strpos($normalized, 'uploads/anhbia/') !== 0) {
			return;
		}

		$candidates = [
			__DIR__ . '/../' . $normalized,
			__DIR__ . '/../../frontend/public/' . $normalized
		];

		foreach ($candidates as $candidate) {
			$fullPath = realpath($candidate);
			if ($fullPath && file_exists($fullPath)) {
				@unlink($fullPath);
				break;
			}
		}
	}

	/**
	 * API: Lấy tất cả truyện (dùng cho truyen_api.php)
	 */
	public function getAllStoriesApi() {
    try {
        $data = $this->truyenModel->getAll();

        // map tac_gia cho đúng với frontend
        $stories = array_map(function($item) {
            if (!isset($item['tac_gia']) && isset($item['ten_tacgia'])) {
                $item['tac_gia'] = $item['ten_tacgia'];
            }
            return $item;
        }, $data);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'stories' => $stories,
                'data'    => $stories
            ]
        ];
    } catch (Exception $e) {
        return [
            'status' => 500,
            'body' => [
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ]
        ];
    }
}

	/**
	 * API: Thêm truyện từ endpoint (dùng cho add_truyen_api.php)
	 * $data: associative array with keys id_tacgia, ten_truyen, mo_ta, trang_thai, anh_bia (FILE array or string), theloai_ids
	 */
	public function addTruyenApi($data) {
		$errors = [];

		$id_tacgia = isset($data['id_tacgia']) ? trim($data['id_tacgia']) : '';
		$ten_truyen = isset($data['ten_truyen']) ? trim($data['ten_truyen']) : '';
		$mo_ta = isset($data['mo_ta']) ? trim($data['mo_ta']) : '';
		$trang_thai = isset($data['trang_thai']) ? trim($data['trang_thai']) : '';
		$theloai_ids = $data['theloai_ids'] ?? [];

		if ($id_tacgia === '') {
			$errors[] = 'Vui lòng chọn tác giả';
		}
		if ($ten_truyen === '') {
			$errors[] = 'Vui lòng nhập tên truyện';
		}

		// normalize theloai_ids to array of ints
		if (!is_array($theloai_ids)) {
			if (is_string($theloai_ids) && strlen($theloai_ids) > 0) {
				$theloai_ids = explode(',', $theloai_ids);
			} else {
				$theloai_ids = [];
			}
		}

		if (!empty($errors)) {
			return [
				'status' => 400,
				'body' => [
					'success' => false,
					'message' => 'Dữ liệu không hợp lệ',
					'errors' => $errors
				]
			];
		}

		// Handle file upload if provided (expects $_FILES style array)
		$anh_bia_db_path = '';
		if (!empty($data['anh_bia']) && is_array($data['anh_bia']) && isset($data['anh_bia']['tmp_name'])) {
			$file = $data['anh_bia'];
			if ($file['error'] === UPLOAD_ERR_OK) {
				$allowed = ['image/jpeg','image/jpg','image/png','image/gif'];
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mime = finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);

				if (!in_array($mime, $allowed)) {
					return [
						'status' => 400,
						'body' => [
							'success' => false,
							'message' => 'File ảnh không hợp lệ'
						]
					];
				}

				$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
				$filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

				$destDir = $this->getCoverUploadDir();

				$destPath = $destDir . DIRECTORY_SEPARATOR . $filename;

				if (!move_uploaded_file($file['tmp_name'], $destPath)) {
					return [
						'status' => 500,
						'body' => [
							'success' => false,
							'message' => 'Không thể lưu file ảnh'
						]
					];
				}

				// Keep DB path as uploads/anhbia/... for compatibility.
				$anh_bia_db_path = 'uploads/anhbia/' . $filename;
			}
		} elseif (!empty($data['anh_bia']) && is_string($data['anh_bia'])) {
			// JSON fallback: accept existing path and normalize known prefixes.
			$anh_bia_db_path = $this->normalizeCoverDbPath($data['anh_bia']);
		}

		$insertData = [
			'id_tacgia' => $id_tacgia,
			'ten_truyen' => $ten_truyen,
			'mo_ta' => $mo_ta,
			'trang_thai' => $trang_thai,
			'anh_bia' => $anh_bia_db_path
		];

		try {
			$this->truyenModel->create($insertData);
			$newId = $this->truyenModel->getLastInsertId();

			// insert categories
			foreach ($theloai_ids as $tid) {
				$tid = intval($tid);
				if ($tid > 0) {
					$this->truyenModel->insertTheLoai($newId, $tid);
				}
			}

			return [
				'status' => 201,
				'body' => [
					'success' => true,
					'message' => 'Thêm truyện thành công',
					'data' => ['id' => $newId]
				]
			];

		} catch (Exception $e) {
			if ($e->getMessage() === 'DUPLICATE_ENTRY') {
				return [
					'status' => 409,
					'body' => [
						'success' => false,
						'message' => 'Truyện đã tồn tại'
					]
				];
			}

			return [
				'status' => 500,
				'body' => [
					'success' => false,
					'message' => 'Lỗi server: ' . $e->getMessage()
				]
			];
		}
	}
	public function getTruyenByIdApi($id) {
    $id = intval($id);

    if ($id <= 0) {
        return [
            'status' => 400,
            'body' => [
                'success' => false,
                'message' => 'ID không hợp lệ'
            ]
        ];
    }

    try {
        $truyen = $this->truyenModel->getById($id);

        if (!$truyen) {
            return [
                'status' => 404,
                'body' => [
                    'success' => false,
                    'message' => 'Không tìm thấy truyện'
                ]
            ];
        }

        // Lấy thể loại của truyện
        $theloais = $this->truyenModel->getTheLoaiByTruyen($id);
        $truyen['theloai_ids'] = array_column($theloais, 'id_theloai');

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'truyen' => $truyen
            ]
        ];

    } catch (Exception $e) {
        return [
            'status' => 500,
            'body' => [
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ]
        ];
    }
}

	/**
	 * API: Cập nhật truyện (dùng cho edit_truyen_api.php)
	 * $data phải chứa 'id' hoặc sẽ trả lỗi
	 */
	public function updateTruyenApi($data) {
		$errors = [];

		$id = isset($data['id']) ? intval($data['id']) : 0;
		if ($id <= 0) {
			return [
				'status' => 400,
				'body' => [
					'success' => false,
					'message' => 'ID truyện không hợp lệ'
				]
			];
		}

		$existing = $this->truyenModel->getById($id);
		if (!$existing) {
			return [
				'status' => 404,
				'body' => [
					'success' => false,
					'message' => 'Không tìm thấy truyện'
				]
			];
		}

		$id_tacgia = isset($data['id_tacgia']) ? trim($data['id_tacgia']) : $existing['id_tacgia'];
		$ten_truyen = isset($data['ten_truyen']) ? trim($data['ten_truyen']) : $existing['ten_truyen'];
		$mo_ta = isset($data['mo_ta']) ? trim($data['mo_ta']) : $existing['mo_ta'];
		$trang_thai = isset($data['trang_thai']) ? trim($data['trang_thai']) : $existing['trang_thai'];
		$theloai_ids = $data['theloai_ids'] ?? [];

		if ($id_tacgia === '') {
			$errors[] = 'Vui lòng chọn tác giả';
		}
		if ($ten_truyen === '') {
			$errors[] = 'Vui lòng nhập tên truyện';
		}

		if (!is_array($theloai_ids)) {
			if (is_string($theloai_ids) && strlen($theloai_ids) > 0) {
				$theloai_ids = explode(',', $theloai_ids);
			} else {
				$theloai_ids = [];
			}
		}

		if (!empty($errors)) {
			return [
				'status' => 400,
				'body' => [
					'success' => false,
					'message' => 'Dữ liệu không hợp lệ',
					'errors' => $errors
				]
			];
		}

		// Handle file upload: if provided, move and replace; otherwise keep existing
		$anh_bia_db_path = $this->normalizeCoverDbPath($existing['anh_bia'] ?? '');
		if (!empty($data['anh_bia']) && is_array($data['anh_bia']) && isset($data['anh_bia']['tmp_name'])) {
			$file = $data['anh_bia'];
			if ($file['error'] === UPLOAD_ERR_OK) {
				$allowed = ['image/jpeg','image/jpg','image/png','image/gif'];
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mime = finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);

				if (!in_array($mime, $allowed)) {
					return [
						'status' => 400,
						'body' => [
							'success' => false,
							'message' => 'File ảnh không hợp lệ'
						]
					];
				}

				$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
				$filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

				$destDir = $this->getCoverUploadDir();

				$destPath = $destDir . DIRECTORY_SEPARATOR . $filename;

				if (!move_uploaded_file($file['tmp_name'], $destPath)) {
					return [
						'status' => 500,
						'body' => [
							'success' => false,
							'message' => 'Không thể lưu file ảnh'
						]
					];
				}

				$this->deleteOldCoverFile($anh_bia_db_path);

				$anh_bia_db_path = 'uploads/anhbia/' . $filename;
			}
		}

		$updateData = [
			'id_tacgia' => $id_tacgia,
			'ten_truyen' => $ten_truyen,
			'mo_ta' => $mo_ta,
			'trang_thai' => $trang_thai,
			'anh_bia' => $anh_bia_db_path
		];

		try {
			// update main table
            $result = $this->truyenModel->update($id, $updateData);
            if (!$result) {
                return [
                    'status' => 500,
                    'body' => [
                    'success' => false,
                    'message' => 'Cập nhật thất bại - kiểm tra lại dữ liệu'
                    ]
                ];
}

			// replace categories: delete existing then insert
			$this->truyenModel->deleteTheLoaiByTruyen($id);
			foreach ($theloai_ids as $tid) {
				$tid = intval($tid);
				if ($tid > 0) {
					$this->truyenModel->insertTheLoai($id, $tid);
				}
			}

			return [
				'status' => 200,
				'body' => [
					'success' => true,
					'message' => 'Cập nhật truyện thành công',
					'data' => ['id' => $id]
				]
			];

		} catch (Exception $e) {
			return [
				'status' => 500,
				'body' => [
					'success' => false,
					'message' => 'Lỗi server: ' . $e->getMessage()
				]
			];
		}
	}
	public function getChiTietTruyenApi($id) {
        $id = intval($id);

        if ($id <= 0) {
            return [
                'status' => 400,
                'body' => ['success' => false, 'message' => 'ID không hợp lệ']
            ];
        }

        try {
            $truyen = $this->truyenModel->getTruyenById($id);

            if (!$truyen) {
                return [
                    'status' => 404,
                    'body' => ['success' => false, 'message' => 'Không tìm thấy truyện']
                ];
            }

            $theloais = $this->truyenModel->getTheLoaiByTruyen($id);
            $truyen['theloai_ids'] = array_column($theloais, 'id_theloai');

            return [
                'status' => 200,
                'body' => ['success' => true, 'truyen' => $truyen]
            ];

        } catch (Exception $e) {
            return [
                'status' => 500,
                'body' => ['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]
            ];
        }
    }
	public function searchTruyenApi($keyword) {
    $keyword = trim($keyword);

    if ($keyword === '') {
        return [
            'status' => 400,
            'body' => [
                'success' => false,
                'message' => 'Vui lòng nhập từ khóa tìm kiếm'
            ]
        ];
    }

    try {
        $data = $this->truyenModel->timKiem($keyword);

        $stories = array_map(function($item) {
            if (!isset($item['tac_gia']) && isset($item['ten_tacgia'])) {
                $item['tac_gia'] = $item['ten_tacgia'];
            }
            return $item;
        }, $data);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'keyword' => $keyword,
                'total'   => count($stories),
                'stories' => $stories
            ]
        ];

    } catch (Exception $e) {
        return [
            'status' => 500,
            'body' => [
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ]
        ];
    }
}
}
?>

