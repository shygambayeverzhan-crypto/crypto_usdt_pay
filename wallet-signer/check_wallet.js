import "dotenv/config";
import { TronWeb } from "tronweb"; // важно: named export

const TARGET = process.env.TARGET_ADDRESS;

const paths = [
    "m/44'/195'/0'/0/0",
    "m/44'/195'/0'/0/1",
    "m/44'/195'/0'/1/0",
    "m/44'/195'/1'/0/0",
];

async function main() {
    if (!TARGET) throw new Error("TARGET_ADDRESS missing");

    const mnemonic = process.env.MNEMONIC;
    if (!mnemonic) throw new Error("MNEMONIC missing");
    if (!process.env.TRON_FULLHOST) throw new Error("TRON_FULLHOST missing");
    if (!process.env.USDT_CONTRACT) throw new Error("USDT_CONTRACT missing");

    for (const path of paths) {
        // v6: TronWeb.fromMnemonic(mnemonic, path, password, wordlist)  [oai_citation:1‡tronweb.network](https://tronweb.network/docu/docs/API%20List/utils/fromMnemonic/)
        const acc = TronWeb.fromMnemonic(mnemonic, path, "");

        const addr = acc.address; // base58
        console.log(`path: ${path}`);
        console.log(`addr: ${addr} ${addr === TARGET ? "✅ MATCH" : ""}`);

        if (addr === TARGET) {
            const pk = acc.privateKey.startsWith("0x") ? acc.privateKey.slice(2) : acc.privateKey;

            const tronWeb = new TronWeb({
                fullHost: process.env.TRON_FULLHOST,
                headers: process.env.TRON_API_KEY ? { "TRON-PRO-API-KEY": process.env.TRON_API_KEY } : {},
                privateKey: pk,
            });

            const trxSun = await tronWeb.trx.getBalance(addr);
            console.log("TRX:", trxSun / 1_000_000);

            const c = await tronWeb.contract().at(process.env.USDT_CONTRACT);
            const name = await c.name().call();
            const symbol = await c.symbol().call();
            const decimals = await c.decimals().call();
            const bal = await c.balanceOf(addr).call();

            console.log("Token:", { name, symbol, decimals: Number(decimals) });
            console.log("Balance(raw):", bal.toString());
            return;
        }

        console.log("-----");
    }

    console.log("❌ Не нашли путь из списка. Тогда будем перебирать индексы /0/0..../0/50.");
}

main().catch((e) => {
    console.error(e);
    process.exit(1);
});
