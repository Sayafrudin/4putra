import xlsx from 'xlsx';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const MIN_SUPPORT = 3;     
const MIN_CONFIDENCE = 70; 

function eksekusiAprioriLengkap() {
    const pathBerkas = path.join(__dirname, 'transaksi.xlsx');
    const workbook = xlsx.readFile(pathBerkas);
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const dataRaw = xlsx.utils.sheet_to_json(sheet);

    const transaksiGroup = {};
    
    // TAHAP 1: PREPROCESSING & DATA CLEANING
    dataRaw.forEach(row => {
        const idTransaksi = row['NO'];
        const teksBarang = row['BARANG'] ? row['BARANG'].toString() : '';

        if (idTransaksi && teksBarang) {
            if (!transaksiGroup[idTransaksi]) {
                transaksiGroup[idTransaksi] = [];
            }

            teksBarang.split(',').forEach(item => {
                const itemBersih = item.trim().toLowerCase().replace(/\s+/g, ' ');
                if (itemBersih && itemBersih.length > 4) {
                    transaksiGroup[idTransaksi].push(itemBersih);
                }
            });

            transaksiGroup[idTransaksi] = [...new Set(transaksiGroup[idTransaksi])];
        }
    });

    const daftarTransaksi = Object.values(transaksiGroup).filter(t => t.length > 0);
    const totalSemuaTransaksi = daftarTransaksi.length;

    // TAHAP 2: PERHITUNGAN FREKUENSI K-ITEMSET MURNI
    const hitungSupport = {};
    daftarTransaksi.forEach(isiKeranjang => {
        const itemUnik = [...isiKeranjang].sort();

        // 1-Itemset
        itemUnik.forEach(b1 => {
            hitungSupport[b1] = (hitungSupport[b1] || 0) + 1;

            // 2-Itemset
            itemUnik.forEach(b2 => {
                if (b1 < b2) {
                    const k2 = `${b1}, ${b2}`;
                    hitungSupport[k2] = (hitungSupport[k2] || 0) + 1;

                    // 3-Itemset
                    itemUnik.forEach(b3 => {
                        if (b2 < b3) {
                            const k3 = `${b1}, ${b2}, ${b3}`;
                            hitungSupport[k3] = (hitungSupport[k3] || 0) + 1;
                        }
                    });
                }
            });
        });
    });

    const kandidatAturanAtas = [];
    const strongRulesFinal = [];

    // TAHAP 3: PEMBENTUKAN KANDIDAT ATURAN DAN STRONG RULES
    Object.keys(hitungSupport).forEach(itemset => {
        const isiItemset = itemset.split(', ');
        const hitungMurniSupportGlobal = (hitungSupport[itemset] / totalSemuaTransaksi) * 100;

        // Hanya memproses itemset yang berstatus LOLOS seleksi support (>= 3%)
        if (hitungMurniSupportGlobal >= MIN_SUPPORT) {
            
            // Ekstraksi dari 3-Itemset (L-3)
            if (isiItemset.length === 3) {
                for (let i = 0; i < isiItemset.length; i++) {
                    const consequent = isiItemset[i];
                    const antecedentList = isiItemset.filter(item => item !== consequent).sort();
                    const antecedentKey = antecedentList.join(', ');

                    const jumlahBersama = hitungSupport[itemset];
                    const jumlahAntecedent = hitungSupport[antecedentKey] || 0;
                    const jumlahConsequent = hitungSupport[consequent] || 0;

                    if (jumlahAntecedent > 0) {
                        const confidence = (jumlahBersama / jumlahAntecedent) * 100;
                        const anteSupport = (jumlahAntecedent / totalSemuaTransaksi) * 100;
                        const consSupport = (jumlahConsequent / totalSemuaTransaksi) * 100;

                        const statusLolos = confidence >= MIN_CONFIDENCE ? '✅ LOLOS' : '❌ TIDAK LOLOS';

                        const dataAturan = {
                            antecedents: formatNamaBurung(antecedentList.join(', ')),
                            consequents: formatNamaBurung(consequent),
                            antecedentSupport: `${anteSupport.toFixed(4)}%`,
                            consequentSupport: `${consSupport.toFixed(4)}%`,
                            supportGlobal: `${hitungMurniSupportGlobal.toFixed(4)}%`,
                            confidence: `${confidence.toFixed(2)}%`,
                            status: statusLolos
                        };

                        kandidatAturanAtas.push(dataAturan);
                        if (confidence >= MIN_CONFIDENCE) {
                            strongRulesFinal.push(dataAturan);
                        }
                    }
                }
            }
            // Ekstraksi dari 2-Itemset (L-2)
            else if (isiItemset.length === 2) {
                for (let i = 0; i < 2; i++) {
                    const antecedent = isiItemset[i];
                    const consequent = isiItemset[1 - i];

                    const jumlahBersama = hitungSupport[itemset];
                    const jumlahAntecedent = hitungSupport[antecedent] || 0;
                    const jumlahConsequent = hitungSupport[consequent] || 0;

                    if (jumlahAntecedent > 0) {
                        const confidence = (jumlahBersama / jumlahAntecedent) * 100;
                        const anteSupport = (jumlahAntecedent / totalSemuaTransaksi) * 100;
                        const consSupport = (jumlahConsequent / totalSemuaTransaksi) * 100;

                        const statusLolos = confidence >= MIN_CONFIDENCE ? '✅ LOLOS' : '❌ TIDAK LOLOS';

                        const dataAturan = {
                            antecedents: formatNamaBurung(antecedent),
                            consequents: formatNamaBurung(consequent),
                            antecedentSupport: `${anteSupport.toFixed(4)}%`,
                            consequentSupport: `${consSupport.toFixed(4)}%`,
                            supportGlobal: `${hitungMurniSupportGlobal.toFixed(4)}%`,
                            confidence: `${confidence.toFixed(2)}%`,
                            status: statusLolos
                        };

                        kandidatAturanAtas.push(dataAturan);
                        if (confidence >= MIN_CONFIDENCE) {
                            strongRulesFinal.push(dataAturan);
                        }
                    }
                }
            }
        }
    });

    kandidatAturanAtas.sort((a, b) => parseFloat(b.confidence) - parseFloat(a.confidence));
    strongRulesFinal.sort((a, b) => parseFloat(b.confidence) - parseFloat(a.confidence));

    const previewKandidat = [];
    Object.keys(hitungSupport).forEach(key => {
        const itemLen = key.split(', ').length;
        const hitungMurniSupport = (hitungSupport[key] / totalSemuaTransaksi) * 100;
        const statusKelulusan = hitungMurniSupport >= MIN_SUPPORT ? '✅ LOLOS' : '❌ TIDAK LOLOS';

        previewKandidat.push({ 
            itemset: formatNamaBurung(key), 
            jenis: `${itemLen}-Itemset`, 
            support: `${hitungMurniSupport.toFixed(2)}%`,
            status: statusKelulusan
        });
    });

    return {
        totalDatabaseTransaksi: totalSemuaTransaksi,
        kandidatItemset: previewKandidat,
        kandidatAturanSaring: kandidatAturanAtas,
        aturanKuatFinal: strongRulesFinal
    };
}

function formatNamaBurung(str) {
    return str.split(' ').map(word => {
        if (word === '&' || word === 'bng') return word.toUpperCase();
        return word.charAt(0).toUpperCase() + word.slice(1);
    }).join(' ');
}

export { eksekusiAprioriLengkap };