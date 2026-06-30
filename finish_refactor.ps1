$files = @(
    "C:\xampp\htdocs\klinik_new\application\controllers\Pengaturan.php",
    "C:\xampp\htdocs\klinik_new\application\controllers\Pengembang.php",
    "C:\xampp\htdocs\klinik_new\application\models\Auth_model.php",
    "C:\xampp\htdocs\klinik_new\application\models\Buka_peta.php",
    "C:\xampp\htdocs\klinik_new\application\models\Forum_model.php",
    "C:\xampp\htdocs\klinik_new\application\models\User_model.php"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        $content = Get-Content -Path $file -Raw
        $original = $content
        
        # Pengaturan.php
        $content = $content -replace "if \(`$this->db->count_all_results\('users'\) > 0\) \{", "if (`$this->db->count_all_results('usr_users') > 0) {"
        
        # Pengembang.php
        $content = $content -replace "`$datacontent\['perumahan'\]= `$this->Buka_peta->frd\('sosmed_perumahan',null,null,null,null\);", "`$datacontent['perumahan']= `$this->Buka_peta->frd('data_sosmed_perumahan',null,null,null,null);"
        
        # Auth_model.php / User_model.php
        $content = $content -replace "insert\('users'", "insert('usr_users'"
        $content = $content -replace "get_where\('users'", "get_where('usr_users'"
        $content = $content -replace "get\('users'\)", "get('usr_users')"
        $content = $content -replace "update\('users'", "update('usr_users'"
        $content = $content -replace "delete\('users'\)", "delete('usr_users')"
        $content = $content -replace "insert\('user_documents'", "insert('usr_documents'"
        $content = $content -replace "get_where\('user_documents'", "get_where('usr_documents'"
        
        # User_model.php additions
        $content = $content -replace "update\('diskusi'", "update('forum_diskusi'"
        $content = $content -replace "update\('komentar'", "update('forum_komentar'"
        
        # Buka_peta.php
        $content = $content -replace "`$menu = `$this->frd\('menu', `$nilai, 'id', null, null\);", "`$menu = `$this->frd('sys_menu', `$nilai, 'id', null, null);"
        $content = $content -replace "`$query = `$this->db->get\('menu'\);", "`$query = `$this->db->get('sys_menu');"
        $content = $content -replace "`$this->db->from\('multi'\);", "`$this->db->from('sys_multi');"
        $content = $content -replace "`$this->db->join\('menu', `"multi\.id_menu = menu\.id`"\);", "`$this->db->join('sys_menu', `"sys_multi.id_menu = sys_menu.id`");"
        
        # Forum_model.php
        $content = $content -replace "from\('diskusi'\)", "from('forum_diskusi')"
        $content = $content -replace "join\('komentar', 'diskusi\.id_diskusi = komentar\.id_diskusi AND komentar\.is_deleted = 0'", "join('forum_komentar', 'forum_diskusi.id_diskusi = forum_komentar.id_diskusi AND forum_komentar.is_deleted = 0'"
        $content = $content -replace "diskusi\.is_deleted", "forum_diskusi.is_deleted"
        $content = $content -replace "diskusi\.judul_topik", "forum_diskusi.judul_topik"
        $content = $content -replace "diskusi\.isi_diskusi", "forum_diskusi.isi_diskusi"
        $content = $content -replace "diskusi\.kategori", "forum_diskusi.kategori"
        $content = $content -replace "diskusi\.id_diskusi", "forum_diskusi.id_diskusi"
        $content = $content -replace "diskusi\.created_at", "forum_diskusi.created_at"
        $content = $content -replace "get_where\('diskusi'", "get_where('forum_diskusi'"
        $content = $content -replace "get_where\('komentar'", "get_where('forum_komentar'"
        $content = $content -replace "insert\('diskusi'", "insert('forum_diskusi'"
        $content = $content -replace "insert\('komentar'", "insert('forum_komentar'"
        $content = $content -replace "`$table = \(`$target_type === 'diskusi'\) \? 'diskusi' : 'komentar';", "`$table = (`$target_type === 'diskusi') ? 'forum_diskusi' : 'forum_komentar';"
        
        if ($original -ne $content) {
            Set-Content -Path $file -Value $content -Encoding UTF8
            Write-Host "Updated $file"
        }
    }
}
Write-Host "Done"
