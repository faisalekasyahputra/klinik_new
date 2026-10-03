<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Susunan MIME untuk HTML dengan gambar tertanam (CID).
 *
 * CI_Email 3.1.13 menyusun multipart/related > [multipart/alternative > (teks, HTML), gambar]. Akar
 * bagian related di situ adalah alternative, bukan HTML, sehingga Gmail menampilkan gambar tertanam
 * sebagai lampiran (logo-jateng.png muncul sebagai chip di kotak masuk, 3 Okt 2026). Susunan baku:
 * multipart/alternative > [teks, multipart/related > (HTML, gambar)]. Lampiran biasa (mixed) dan
 * protokol mail() tetap memakai susunan bawaan. Uji: docs/engineering/uji_email_mime.php.
 */
class MY_Email extends CI_Email {

    protected function _build_message() {
        if ($this->_get_content_type() !== 'html-attach' || $this->_get_protocol() === 'mail'
            || $this->_attachments_have_multipart('mixed')) {
            return parent::_build_message();
        }

        $this->_write_headers();
        $nl  = $this->newline;
        $alt = uniqid('B_ALT_');
        $rel = uniqid('B_REL_');

        $body = $this->_get_mime_message() . $nl . $nl
            . '--' . $alt . $nl
            . 'Content-Type: text/plain; charset=' . $this->charset . $nl
            . 'Content-Transfer-Encoding: ' . $this->_get_encoding() . $nl . $nl
            . $this->_get_alt_message() . $nl . $nl
            . '--' . $alt . $nl
            . 'Content-Type: multipart/related; boundary="' . $rel . '"' . $nl . $nl
            . '--' . $rel . $nl
            . 'Content-Type: text/html; charset=' . $this->charset . $nl
            . 'Content-Transfer-Encoding: quoted-printable' . $nl . $nl
            . $this->_prep_quoted_printable($this->_body) . $nl . $nl;
        $this->_append_attachments($body, $rel, 'related');
        $body .= $nl . $nl . '--' . $alt . '--';

        $this->_finalbody = 'Content-Type: multipart/alternative; boundary="' . $alt . '"' . $nl . $nl . $body;
        return TRUE;
    }
}
