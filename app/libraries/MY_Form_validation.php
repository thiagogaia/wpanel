<?php

/**
 * WPanel CMS
 *
 * An open source Content Manager System for websites and systems using CodeIgniter.
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2008 - 2017, Eliel de Paula.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package     WpanelCms
 * @author      Eliel de Paula <dev@elieldepaula.com.br>
 * @copyright   Copyright (c) 2008 - 2017, Eliel de Paula. (https://elieldepaula.com.br/)
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://wpanel.org
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This library bring more methods to validade Captcha, CPF and CNPJ (Brasilian documents)
 * on CodeIgniter.
 * 
 * CAUTION!!!
 * ----------
 * Be sure to have GD library in your environment.
 */
class MY_Form_validation extends CI_Form_validation
{

    /**
     * Construtor da classe.
     * */
    function __construct()
    {
        parent::__construct();
    }

    public function captcha()
    {
        // First, delete old captchas
        $expiration = time() - 7200; // Two hour limit
        $this->CI->db->query("DELETE FROM captcha WHERE captcha_time < " . $expiration);

        // Then see if a captcha exists:
        $sql = "SELECT COUNT(*) AS count FROM captcha WHERE word = ? AND ip_address = ? AND captcha_time > ?";
        $binds = array($_POST['captcha'], $this->CI->input->ip_address(), $expiration);
        $query = $this->CI->db->query($sql, $binds);
        $row = $query->row();

        if ($row->count == 0)
        {
            $this->set_message('captcha', 'O texto de confirmação não é válido.'); //TODO Usar o arquivo de tradução. lang('captcha_err'));
            return FALSE;
        } else
        {
            return TRUE;
        }
    }

    /**
     * Este método executa a bilbioteca 'Captcha' do CodeIgniter.
     * */
    function get_captcha()
    {
        $vals = array(
            'word' => $this->gen_rand_shortcode(6),
            'img_path' => FCPATH . 'captcha/',
            'img_url' => base_url('captcha') . '/',
            'font_path' => FCPATH . 'lib/fonts/essai.ttf',
            'img_width' => '150',
            'img_height' => '50',
            'expiration' => 7200,
            'colors' => array(
                'background' => array(255, 100, 80),
                'border' => array(0, 0, 80),
                'text' => array(40, 40, 40),
                'grid' => array(255, 40, 40)
            )
        );

        $cap = create_captcha($vals);

        $data = array(
            'captcha_time' => $cap['time'],
            'ip_address' => $this->CI->input->ip_address(),
            'word' => $cap['word']
        );

        $this->CI->load->database();
        $query = $this->CI->db->insert_string('captcha', $data);
        $this->CI->db->query($query);

        return $cap['image'];
    }

    /**
     * Este método gera o código aleatório
     * para ser impresso na imagem.
     * */
    private function gen_rand_shortcode($length)
    {
        $randstr = "";
        for ($i = 0; $i < $length; $i++)
        {
            $randnum = mt_rand(0, 61);
            if ($randnum < 10)
            {
                $randstr .= chr($randnum + 48);
            } else if ($randnum < 36)
            {
                $randstr .= chr($randnum + 55);
            } else
            {
                $randstr .= chr($randnum + 61);
            }
        }
        return $randstr;
    }

    /**
     *
     * valid_cpf
     *
     * Verifica  o CPF é válido.
     * @access	public
     * @param	string
     * @return	bool
     */
    function valid_cpf($cpf)
    {
        $CI = & get_instance();
        $CI->form_validation->set_message('valid_cpf', 'O %s informado não é válido.');
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11 || preg_match('/^([0-9])\1+$/', $cpf))
        {
            return false;
        }
        $digit = substr($cpf, 0, 9);
        for ($j = 10; $j <= 11; $j++)
        {
            $sum = 0;
            for ($i = 0; $i < $j - 1; $i++)
            {
                $sum += ($j - $i) * ((int) $digit[$i]);
            }
            $summod11 = $sum % 11;
            $digit[$j - 1] = $summod11 < 2 ? 0 : 11 - $summod11;
        }
        return $digit[9] == ((int) $cpf[9]) && $digit[10] == ((int) $cpf[10]);
    }

    /**
     * Verifica se o CNPJ é válido.
     * @param     string
     * @return     bool
     */
    function valid_cnpj($str)
    {
        $CI = & get_instance();
        $CI->form_validation->set_message('valid_cnpj', 'O %s informado não é válido.');
        if (strlen($str) <> 18)
            return FALSE;
        $soma1 = ($str[0] * 5) + ($str[1] * 4) + ($str[3] * 3) + ($str[4] * 2) + ($str[5] * 9) + ($str[7] * 8) + ($str[8] * 7) + ($str[9] * 6) + ($str[11] * 5) + ($str[12] * 4) + ($str[13] * 3) + ($str[14] * 2);
        $resto = $soma1 % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;
        $soma2 = ($str[0] * 6) + ($str[1] * 5) + ($str[3] * 4) + ($str[4] * 3) + ($str[5] * 2) + ($str[7] * 9) + ($str[8] * 8) + ($str[9] * 7) + ($str[11] * 6) + ($str[12] * 5) + ($str[13] * 4) + ($str[14] * 3) + ($str[16] * 2);
        $resto = $soma2 % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;
        return (($str[16] == $digito1) && ($str[17] == $digito2));
    }

}
