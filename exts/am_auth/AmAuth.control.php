<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2015 Sir Ideas, C. A.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 **/

class AmAuthControl extends AmControl{

  // Bandeja de la administracion
  public function action_signin(){}
  public function get_signin(){}
  public function post_signin(){

    // Obtener el nombre de la clase
    $class = $this->authClass;

    // Busca el usuario por usernam y por password
    $user = $class::auth(
      $this->post->signin["username"],
      $this->post->signin["password"]
    );

    if(isset($user)){

      // Usuario esta autenticado
      Am::getCredentialsHandler()->setAuthenticated($user);
      AmFlash::success($this->texts["welcome"]);
      Am::gotoUrl($this->urls["index"]);  // Ir a index

    }else{

      // Usuario no autenticado
      AmFlash::danger($this->texts["authFailed"]);

    }

  }

  public function action_signout(){
    Am::getCredentialsHandler()->setAuthenticated($user);
    Am::gotoUrl($this->urls["index"]);  // Ir a index
  }

}
