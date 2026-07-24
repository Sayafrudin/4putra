import { eksekusiAprioriLengkap } from './apriori.js';

const dataHasil = eksekusiAprioriLengkap();

if (!dataHasil) {
    console.log('Gagal memproses data.');
    process.exit(1);
}

console.log('=========================================================================================================================');
console.log(`                             LAPORAN DATA MINING LENGKAP APRIORI - PT 4PUTRA VERTEX`);
console.log(`                                 TOTAL DATASET HISTORI TRANSAKSI: ${dataHasil.totalDatabaseTransaksi} BARIS`);
console.log('=========================================================================================================================\n');

// ==========================================
// TAHAP 1: MENAMPILKAN KANDIDAT K-ITEMSET
// ==========================================
console.log('-------------------------------------------------------------------------------------------------------------------------');
console.log('TAHAP 1: EVALUASI KANDIDAT K-ITEMSET (MINIMUM SUPPORT >= 3%)');
console.log('-------------------------------------------------------------------------------------------------------------------------');

const itemset1 = dataHasil.kandidatItemset.filter(i => i.jenis === '1-Itemset');
const itemset2 = dataHasil.kandidatItemset.filter(i => i.jenis === '2-Itemset');
const itemset3 = dataHasil.kandidatItemset.filter(i => i.jenis === '3-Itemset');

console.log(`\n[A] Evaluasi Frekuensi 1-Itemset:`);
itemset1.forEach((item, index) => {
    console.log(`    ${index + 1}. Burung: [${item.itemset.padEnd(25)}] | Support: ${item.support.padEnd(6)} | Status: ${item.status}`);
});

console.log(`\n[B] Evaluasi Frekuensi 2-Itemset:`);
itemset2.forEach((item, index) => {
    console.log(`    ${index + 1}. Kombinasi: [${item.itemset.padEnd(42)}] | Support: ${item.support.padEnd(6)} | Status: ${item.status}`);
});

console.log(`\n[C] Evaluasi Frekuensi 3-Itemset:`);
itemset3.forEach((item, index) => {
    console.log(`    ${index + 1}. Kombinasi: [${item.itemset.padEnd(45)}] | Support: ${item.support.padEnd(6)} | Status: ${item.status}`);
});


// ==========================================
// TAHAP 2: PEMBENTUKAN KANDIDAT ATURAN DARI L-2 DAN L-3
// ==========================================
console.log('\n-------------------------------------------------------------------------------------------------------------------------');
console.log('TAHAP 2: PEMBENTUKAN KANDIDAT ATURAN DARI L-2 DAN L-3 (MENGUJI SEMUA KEMUNGKINAN ATURAN)');
console.log('-------------------------------------------------------------------------------------------------------------------------');

console.log(''.padEnd(140, '-'));
console.log(
    '| ' + 'Antecedents (Jika)'.padEnd(30) + 
    '| ' + 'Consequents (Maka)'.padEnd(20) + 
    '| ' + 'Ante.Sup'.padEnd(12) + 
    '| ' + 'Cons.Sup'.padEnd(12) + 
    '| ' + 'Support'.padEnd(12) + 
    '| ' + 'Confidence'.padEnd(12) + 
    '| ' + 'Status Saringan (>=70%)'.padEnd(23) + ' |'
);
console.log(''.padEnd(140, '-'));

dataHasil.kandidatAturanSaring.forEach(rule => {
    console.log(
        '| ' + rule.antecedents.padEnd(30) + 
        '| ' + rule.consequents.padEnd(20) + 
        '| ' + rule.antecedentSupport.padEnd(12) + 
        '| ' + rule.consequentSupport.padEnd(12) + 
        '| ' + rule.supportGlobal.padEnd(12) + 
        '| ' + rule.confidence.padEnd(12) + 
        '| ' + rule.status.padEnd(23) + ' |'
    );
});
console.log(''.padEnd(140, '-'));


// ==========================================
// TAHAP 3: HASIL STRONG ASSOCIATION RULES (CONFIDENCE)
// ==========================================
console.log('\n-------------------------------------------------------------------------------------------------------------------------');
console.log('TAHAP 3: HASIL STRONG ASSOCIATION RULES (HANYA ATURAN YANG MEMENUHI MINIMUM CONFIDENCE >= 70%)');
console.log('-------------------------------------------------------------------------------------------------------------------------');

if (dataHasil.aturanKuatFinal.length === 0) {
    console.log('⚠️ Tidak ada aturan asosiasi yang berhasil lolos kriteria strong rules.');
} else {
    console.log(''.padEnd(115, '-'));
    console.log(
        '| ' + 'Antecedents (Jika)'.padEnd(30) + 
        '| ' + 'Consequents (Maka)'.padEnd(20) + 
        '| ' + 'Ante.Sup'.padEnd(12) + 
        '| ' + 'Cons.Sup'.padEnd(12) + 
        '| ' + 'Support'.padEnd(12) + 
        '| ' + 'Confidence'.padEnd(12) + ' |'
    );
    console.log(''.padEnd(115, '-'));

    dataHasil.aturanKuatFinal.forEach(rule => {
        console.log(
            '| ' + rule.antecedents.padEnd(30) + 
            '| ' + rule.consequents.padEnd(20) + 
            '| ' + rule.antecedentSupport.padEnd(12) + 
            '| ' + rule.consequentSupport.padEnd(12) + 
            '| ' + rule.supportGlobal.padEnd(12) + 
            '| ' + rule.confidence.padEnd(12) + ' |'
        );
    });
    console.log(''.padEnd(115, '-'));
    console.log(`\n🎉 SUKSES: Ditemukan ${dataHasil.aturanKuatFinal.length} Strong Association Rules yang siap disuntikkan ke Chatbot WhatsApp.`);
}

console.log('\n=========================================================================================================================');