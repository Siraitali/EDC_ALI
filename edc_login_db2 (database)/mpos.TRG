TYPE=TRIGGERS
triggers='CREATE DEFINER=`root`@`localhost` TRIGGER trg_mpos_insert\nAFTER INSERT ON mpos\nFOR EACH ROW\nBEGIN\n  INSERT INTO notifikasi (mpos_id, jenis, user, status)\n  VALUES (NEW.id, \'input\', \'system\', \'unread\');\nEND' 'CREATE DEFINER=`root`@`localhost` TRIGGER trg_mpos_update\nAFTER UPDATE ON mpos\nFOR EACH ROW\nBEGIN\n  INSERT INTO notifikasi (mpos_id, jenis, user, status)\n  VALUES (NEW.id, \'edit\', \'system\', \'unread\');\nEND'
sql_modes=1098907648 1098907648
definers='root@localhost' 'root@localhost'
client_cs_names='utf8mb4' 'utf8mb4'
connection_cl_names='utf8mb4_unicode_ci' 'utf8mb4_unicode_ci'
db_cl_names='latin1_swedish_ci' 'latin1_swedish_ci'
created=175989471217 175989471238
