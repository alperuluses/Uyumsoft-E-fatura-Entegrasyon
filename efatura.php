<?php
class newInvoiceSend
{
    const apiURL = "http://efatura.uyumsoft.com.tr/api/BasicIntegrationApi";
    const PartyName = "www.example.com";
    const PartyInfo = [
        "StreetName" => "İTU AYAZAĞA YERLEŞKESİ",
        "CitySubdivisionName" => "SARIYER",
        "CityName" => "İSTANBUL",
        "Country" => "Türkiye",
        "PartyTaxScheme" => "SARIYER",
        "Person" => [
            "FirstName" => "Alper",
            "FamilyName" => "Uluses"
        ]
    ];
    const Vkn = "11111111111"; // Vekil kimlik no(VKN) - eğer şahıs ise TCKN
    const mersisNo = "";
    const ticaretSicilNo = "";
    private $issueDate;
    private $issueTime;
    private $orders;
    private $customer;
    private $customerSendMail;
    private $orderNumber;
    public function __construct($date, $time, $order, $customerTc, $customerName, $customerSurname, $customerAddress, $customerPhoneNumber, $customerEmail, $orderNo)
    {
        $this->issueDate = $date;
        $this->issueTime = $time;
        $this->orders = $order;
        $this->customer = [
            "TCKN" => "" . $customerTc . "",
            "Person" => [
                "FirstName" => "" . $customerName . "",
                "FamilyName" => "" . $customerSurname . ""
            ],
            "Room" => "",
            "StreetName" => "" . $customerAddress . "",
            "BuildingNumber" => "",
            "CitySubdivisionName" => "",
            "CityName" => "",
            "Country" => "",
            "Telephone" => "" . $customerPhoneNumber . ""
        ];
        $this->customerSendMail = $customerEmail;
        $this->orderNumber = $orderNo;
    }

    private $LineCountNumeric = "1";
    //Satışı yapılan müşteri bilgileri.

    private $taxExclusiveTotalPrice;
    private $taxInclusiveTotalPrice;
    private $productsJson = "";
    //KDV'li Tutarın Kdvsi

    public function KdvAmount($taxExclusivePrice)
    {
        $TaxAmount = 0;
        $productPrice = floatval(str_replace(",", ".", $taxExclusivePrice));
        $TaxAmount = $productPrice - ($productPrice / 1.18);
        return round($TaxAmount, 2);
    }

    // Birim KDV'siz fiyatın 
    public function  unitTaxExclusivePrice($data)
    {
        $unitPrice = 0;
        $unitPrice = $data / 1.18;
        return round($unitPrice, 2);
    }

    //Toplam KDV'siz Fiyat 
    public function totalExclusiveTaxPrice($data)
    {
        $this->taxExclusiveTotalPrice = 0;
        foreach ($data as $value) {
            $productPrice = floatval(str_replace(",", ".", $value->fiyat));
            $this->taxExclusiveTotalPrice += ($productPrice / 1.18) * $value->adet;
        }
        return round($this->taxExclusiveTotalPrice, 2);
    }

    //Toplam KDV'li fiyat
    public function totalInclusiveTaxPrice($data)
    {
        $this->taxInclusiveTotalPrice = 0;
        foreach ($data as $value) {
            $productPrice = floatval(str_replace(",", ".", $value->fiyat));
            $this->taxInclusiveTotalPrice += $productPrice * $value->adet;
        }
        return round($this->taxInclusiveTotalPrice, 2);
    }

    //Toplam KDV
    public function totalTaxPrice($data)
    {
        return round($this->totalInclusiveTaxPrice($data) -  $this->totalExclusiveTaxPrice($data), 2);
    }
    function sayiyiYaziyaCevir($sayi, $kurusbasamak, $parabirimi, $parakurus, $diyez, $bb1, $bb2, $bb3)
    {
        // kurusbasamak virgülden sonra gösterilecek basamak sayısı
        // parabirimi = TL gibi , parakurus = Kuruş gibi
        // diyez başa ve sona kapatma işareti atar # gibi

        $b1 = array("", "Bir ", "İki ", "Üç ", "Dört ", "Beş ", "Altı ", "Yedi ", "Sekiz ", "Dokuz ");
        $b2 = array("", "On ", "Yirmi ", "Otuz ", "Kırk ", "Elli ", "Altmış ", "Yetmiş ", "Seksen ", "Doksan ");
        $b3 = array("", "Yüz ", "Bin ", "Milyon ", "Milyar ", "Trilyon ", "Katrilyon ");

        if ($bb1 != null) { // farklı dil kullanımı yada farklı yazım biçimi için
            $b1 = $bb1;
        }
        if ($bb2 != null) { // farklı dil kullanımı
            $b2 = $bb2;
        }
        if ($bb3 != null) { // farklı dil kullanımı
            $b3 = $bb3;
        }

        $say1 = "";
        $say2 = ""; // say1 virgül öncesi, say2 kuruş bölümü
        $sonuc = "";

        $sayi = str_replace(",", ".", $sayi); //virgül noktaya çevrilir

        $nokta = strpos($sayi, "."); // nokta indeksi

        if ($nokta > 0) { // nokta varsa (kuruş)

            $say1 = substr($sayi, 0, $nokta); // virgül öncesi
            $say2 = substr($sayi, $nokta, strlen($sayi)); // virgül sonrası, kuruş

        } else {
            $say1 = $sayi; // kuruş yoksa
        }

        $son = 0;
        $w = 1; // işlenen basamak
        $sonaekle = 0; // binler on binler yüzbinler vs. için sona bin (milyon,trilyon...) eklenecek mi?
        $kac = strlen($say1); // kaç rakam var?
        $sonint = 0; // işlenen basamağın rakamsal değeri
        $uclubasamak = 0; // hangi basamakta (birler onlar yüzler gibi)
        $artan = 0; // binler milyonlar milyarlar gibi artışları yapar
        $gecici = 0;

        if ($kac > 0) { // virgül öncesinde rakam var mı?

            for ($i = 0; $i < $kac; $i++) {

                $son = $say1[$kac - 1 - $i]; // son karakterden başlayarak çözümleme yapılır.
                $sonint = $son; // işlenen rakam Integer.parseInt(

                if ($w == 1) { // birinci basamak bulunuyor

                    $sonuc = $b1[$sonint] . $sonuc;
                } else if ($w == 2) { // ikinci basamak

                    $sonuc = $b2[$sonint] . $sonuc;
                } else if ($w == 3) { // 3. basamak

                    if ($sonint == 1) {
                        $sonuc = $b3[1] . $sonuc;
                    } else if ($sonint > 1) {
                        $sonuc = $b1[$sonint] . $b3[1] . $sonuc;
                    }
                    $uclubasamak++;
                }

                if ($w > 3) { // 3. basamaktan sonraki işlemler

                    if ($uclubasamak == 1) {

                        if ($sonint > 0) {
                            $sonuc = $b1[$sonint] . $b3[2 + $artan] . $sonuc;
                            if ($artan == 0) { // birbin yazmasını engelle
                                $sonuc = str_replace($b1[1] . $b3[2], $b3[2], $sonuc);
                            }
                            $sonaekle = 1; // sona bin eklendi
                        } else {
                            $sonaekle = 0;
                        }
                        $uclubasamak++;
                    } else if ($uclubasamak == 2) {

                        if ($sonint > 0) {
                            if ($sonaekle > 0) {
                                $sonuc = $b2[$sonint] . $sonuc;
                                $sonaekle++;
                            } else {
                                $sonuc = $b2[$sonint] . $b3[2 + $artan] . $sonuc;
                                $sonaekle++;
                            }
                        }
                        $uclubasamak++;
                    } else if ($uclubasamak == 3) {

                        if ($sonint > 0) {
                            if ($sonint == 1) {
                                $gecici = $b3[1];
                            } else {
                                $gecici = $b1[$sonint] . $b3[1];
                            }
                            if ($sonaekle == 0) {
                                $gecici = $gecici . $b3[2 + $artan];
                            }
                            $sonuc = $gecici . $sonuc;
                        }
                        $uclubasamak = 1;
                        $artan++;
                    }
                }

                $w++; // işlenen basamak

            }
        } // if(kac>0)

        if ($sonuc == "") { // virgül öncesi sayı yoksa para birimi yazma
            $parabirimi = "";
        }

        $say2 = str_replace(".", "", $say2);
        $kurus = "";

        if ($say2 != "") { // kuruş hanesi varsa

            if ($kurusbasamak > 3) { // 3 basamakla sınırlı
                $kurusbasamak = 3;
            }
            $kacc = strlen($say2);
            if ($kacc == 1) { // 2 en az
                $say2 = $say2 . "0"; // kuruşta tek basamak varsa sona sıfır ekler.
                $kurusbasamak = 2;
            }
            if (strlen($say2) > $kurusbasamak) { // belirlenen basamak kadar rakam yazılır
                $say2 = substr($say2, 0, $kurusbasamak);
            }

            $kac = strlen($say2); // kaç rakam var?
            $w = 1;

            for ($i = 0; $i < $kac; $i++) { // kuruş hesabı

                $son = $say2[$kac - 1 - $i]; // son karakterden başlayarak çözümleme yapılır.
                $sonint = $son; // işlenen rakam Integer.parseInt(

                if ($w == 1) { // birinci basamak

                    if ($kurusbasamak > 0) {
                        $kurus = $b1[$sonint] . $kurus;
                    }
                } else if ($w == 2) { // ikinci basamak
                    if ($kurusbasamak > 1) {
                        $kurus = $b2[$sonint] . $kurus;
                    }
                } else if ($w == 3) { // 3. basamak
                    if ($kurusbasamak > 2) {
                        if ($sonint == 1) { // 'biryüz' ü engeller
                            $kurus = $b3[1] . $kurus;
                        } else if ($sonint > 1) {
                            $kurus = $b1[$sonint] . $b3[1] . $kurus;
                        }
                    }
                }
                $w++;
            }
            if ($kurus == "") { // virgül öncesi sayı yoksa para birimi yazma
                $parakurus = "";
            } else {
                $kurus = $kurus . " ";
            }
            $kurus = $kurus . $parakurus; // kuruş hanesine 'kuruş' kelimesi ekler
        }

        $sonuc = $diyez . $sonuc . " " . $parabirimi . " " . $kurus . $diyez;
        return $sonuc;
    }

    // Satırdaki ürün sayısının jsonunu oluşturma fonksiyonu
    public function InvoiceLine($data)
    {
        $this->productsJson = "";
        $i = 1;
        foreach ($data as $val) {
            $fiyat = 0;
            $fiyat = str_replace(",", ".", $val->fiyat);
            $this->productsJson .= '{
                "Id": {
                    "value": "' . $i . '"
                },
                "Note": [
                    {
                        "value": "Satır Notu"
                    }
                ],
                "InvoicedQuantity": {
                    "unitCode": "NIU",
                    "value": "' . $val->adet . '"
                },
                "LineExtensionAmount": {
                    "currencyId": "TRY",
                    "value": "' . ($this->unitTaxExclusivePrice($fiyat) * intval($val->adet)) . '"
                },
                "TaxTotal": {
                    "TaxAmount": {
                        "currencyId": "TRY",
                        "value": "' . $this->KdvAmount($fiyat) . '"
                    },
                    "TaxSubtotal": [
                        {
                            "TaxableAmount": {
                                "currencyId": "TRY",
                                "value": "' . $this->unitTaxExclusivePrice($fiyat) . '"
                            },
                            "TaxAmount": {
                                "currencyId": "TRY",
                                "value": "' . $this->KdvAmount($fiyat) . '"
                            },
                            "Percent": {
                                "value": "18"
                            },
                            "TaxCategory": {
                                "TaxScheme": {
                                    "Name": {
                                        "value": "KDV"
                                    },
                                    "TaxTypeCode": {
                                        "value": "0015"
                                    }
                                }
                            }
                        }
                    ]
                },
                "Item": {
                    "Description": {
                        "value": ""
                    },
                    "Name": {
                        "value": "' . $val->ad . '"
                    },
                    "ModelName": {
                        "value": ""
                    }
                },
                "Price": {
                    "PriceAmount": {
                        "currencyId": "TRY",
                        "value": "' . $this->unitTaxExclusivePrice(floatval(str_replace(",", ".", $fiyat))) . '"
                    },
                }
            },';
            $i++;
        }
        return rtrim($this->productsJson, ',');
    }

    // Curl Çalıştır
    public function sendInvoice()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => '' . self::apiURL . '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
      "Action": "SendInvoice",
      "parameters": {
          "invoices": [
              {
                  "Invoice": {
                      "UblVersionId": {
                          "value": 2.1
                      },
                      "CustomizationId": {
                          "value": "TR1.2"
                      },
                      "ProfileId": {
                          "value": "TICARIFATURA"
                      },
                      "CopyIndicator": {
                          "value": false
                      },
                      "IssueDate": {
                          "value": "' . $this->issueDate . '"
                      },
                      "IssueTime": {
                          "value": "' . $this->issueTime . '"
                      },
                      "InvoiceTypeCode": {
                          "value": "SATIS"
                      },
                      "Note": [
                          {
                              "value": "Fatura Notu -1"
                          },
                          {
                              "value": "#' . $this->sayiyiYaziyaCevir($this->totalInclusiveTaxPrice($this->orders), 2, "Türk Lirası", "Kuruş", "", null, null, null) . '#"
                          }
                      ],
                      "DocumentCurrencyCode": {
                          "value": "TRY"
                      },
                      "LineCountNumeric": {
                          "value": ' . $this->LineCountNumeric . '
                      },
                      "AccountingSupplierParty": {
                          "Party": {
                              "PartyIdentification": [
                                  {
                                      "Id": {
                                          "schemeId": "TCKN",
                                          "value": "' . self::Vkn . '"
                                      }
                                  },
                                  {
                                      "Id": {
                                          "schemeId": "MERSISNO",
                                          "value": "' . self::mersisNo . '"
                                      }
                                  },
                                  {
                                      "Id": {
                                          "schemeId": "TICARETSICILNO",
                                          "value": "' . self::ticaretSicilNo . '"
                                      }
                                  }
                              ],
                              "PartyName": {
                                  "Name": {
                                      "value": "' . self::PartyName . '"
                                  }
                              },
                              "PostalAddress": {
                                  "StreetName": {
                                      "value": "' . self::PartyInfo['StreetName'] . '"
                                  },
                                  "CitySubdivisionName": {
                                      "value": "' . self::PartyInfo['CitySubdivisionName'] . '"
                                  },
                                  "CityName": {
                                      "value": "' . self::PartyInfo['CityName'] . '"
                                  },
                                  "Country": {
                                      "Name": {
                                          "value": "' . self::PartyInfo['Country'] . '"
                                      }
                                  }
                              },
                              "PartyTaxScheme": {
                                  "TaxScheme": {
                                      "Name": {
                                          "value": "' . self::PartyInfo['PartyTaxScheme'] . '"
                                      }
                                  }
                              },
                              "Person": {
                                  "FirstName": {
                                      "value": "' . self::PartyInfo['Person']['FirstName'] . '"
                                  },
                                  "FamilyName": {
                                      "value": "' . self::PartyInfo['Person']['FamilyName'] . '"
                                  }
                              }
                          }
                      },
                      "AccountingCustomerParty": {
                          "Party": {
                              "PartyIdentification": [
                                  {
                                      "ID": {
                                          "schemeID": "TCKN",
                                          "Value": "' . $this->customer['TCKN'] . '"
                                      }
                                  }
                              ],
                              "Person": {
                                  "FirstName": {
                                      "value": "' . $this->customer['Person']['FirstName'] . '"
                                  },
                                  "FamilyName": {
                                      "value": "' . $this->customer['Person']['FamilyName'] . '"
                                  }
                              },
                              "PostalAddress": {
                                  "Room": {
                                      "value": ""
                                  },
                                  "StreetName": {
                                      "value": "' . $this->customer['StreetName'] . '"
                                  },
                                  "BuildingNumber": {
                                      "value": "' . $this->customer['BuildingNumber'] . '"
                                  },
                                  "CitySubdivisionName": {
                                      "value": "' . $this->customer['CitySubdivisionName'] . '"
                                  },
                                  "CityName": {
                                      "value": "' . $this->customer['CityName'] . '"
                                  },
                                  "Country": {
                                      "Name": {
                                          "value": "' . $this->customer['Country'] . '"
                                      }
                                  }
                              },
                              "PartyTaxScheme": {
                                  "TaxScheme": {
                                      "Name": {
                                          "value": ""
                                      }
                                  }
                              },
                              "Contact": {
                                  "Telephone": {
                                      "value": "' . $this->customer['Telephone'] . '"
                                  }
                              }
                          }
                      },
                      "TaxTotal": [
                          {
                              "TaxAmount": {
                                  "currencyId": "TRY",
                                  "value": ' . $this->totalTaxPrice($this->orders) . '
                              },
                              "TaxSubtotal": [
                                  {
                                      "TaxableAmount": {
                                          "currencyId": "TRY",
                                          "value": 136.89
                                      },
                                      "TaxAmount": {
                                          "currencyId": "TRY",
                                          "value":' . $this->totalTaxPrice($this->orders) . '
                                      },
                                      "Percent": {
                                          "value": 18
                                      },
                                      "TaxCategory": {
                                          "TaxScheme": {
                                              "Name": {
                                                  "value": "KDV"
                                              },
                                              "TaxTypeCode": {
                                                  "value": "0015"
                                              }
                                          }
                                      }
                                  }
                              ]
                          }
                      ],
                      "LegalMonetaryTotal": {
                          "LineExtensionAmount": {
                              "currencyId": "TRY",
                              "value": ' . $this->totalExclusiveTaxPrice($this->orders) . '
                          },
                          "TaxExclusiveAmount": {
                              "currencyId": "TRY",
                              "value": ' . $this->totalExclusiveTaxPrice($this->orders) . '
                          },
                          "TaxInclusiveAmount": {
                              "currencyId": "TRY",
                              "value": ' . $this->totalInclusiveTaxPrice($this->orders) . '
                          },
                          "AllowanceTotalAmount": {
                              "currencyId": "TRY"
                          },
                          "PayableAmount": {
                              "currencyId": "TRY",
                              "value": ' . $this->totalInclusiveTaxPrice($this->orders) . '
                          }
                      },
                      "InvoiceLine": [
                        ' . $this->InvoiceLine($this->orders) . '
                      ]
                  },
                  "EArchiveInvoiceInfo": {
                      "DeliveryType": "Electronic"
                  },
                  "Scenario": 0,
                  "Notification": {
                      "Mailing": [
                          {
                              "Subject": "Artı Kutu: ' . $this->orderNumber . ' numaralı sipariş faturanız.",
                              "EnableNotification": true,
                              "To": "' . $this->customerSendMail . '",
                              "Attachment": {
                                  "Xml": true,
                                  "Pdf": true
                              }
                          }
                      ]
                  }, F
                  "LocalDocumentId": "E-FATURA-001"
              }
          ],
          "userInfo": {
              "Username": "test",
              "Password": "test"
          }
      }
  }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}

date_default_timezone_set('Europe/Istanbul');
$currentBillDate = date("Y-m-d");
$currentBillTime = date("H:i:s");
$invoiceSend = new newInvoiceSend($currentBillDate, $currentBillTime, $urunler, $uye["tc"], $uye["ad"], $uye["soyad"], $adresDecode->adres, $uye["telefon"], $uye["eposta"], $siparis_no);
//Bir istek oluştur.
$response = $invoiceSend->sendInvoice();
