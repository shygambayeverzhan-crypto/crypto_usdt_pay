import "dotenv/config";
import { TronWeb } from "tronweb";

async function main() {
    const mnemonic = process.env.MNEMONIC;
    const path = process.env.DERIVATION_PATH || "m/44'/195'/0'/0/0";
    const to = process.env.TO_ADDRESS;
    const amountStr = process.env.AMOUNT;

    if (!mnemonic) throw new Error("MNEMONIC missing");
    if (!process.env.TRON_FULLHOST) throw new Error("TRON_FULLHOST missing");
    if (!process.env.USDT_CONTRACT) throw new Error("USDT_CONTRACT missing");
    if (!to) throw new Error("TO_ADDRESS missing");
    if (!amountStr) throw new Error("AMOUNT missing");

    const acc = TronWeb.fromMnemonic(mnemonic, path, "");
    const pk = acc.privateKey.startsWith("0x") ? acc.privateKey.slice(2) : acc.privateKey;

    const tronWeb = new TronWeb({
        fullHost: process.env.TRON_FULLHOST,
        headers: process.env.TRON_API_KEY ? { "TRON-PRO-API-KEY": process.env.TRON_API_KEY } : {},
        privateKey: pk,
    });

    const contract = await tronWeb.contract().at(process.env.USDT_CONTRACT);

    const decimals = Number(await contract.decimals().call()); // у тебя = 6
    const value = BigInt(Math.round(Number(amountStr) * (10 ** decimals)));

    const txid = await contract.transfer(to, value).send({
        feeLimit: 30_000_000, // 30 TRX лимит
    });

    console.log("FROM:", acc.address);
    console.log("TO:", to);
    console.log("TXID:", txid);
}

main().catch((e) => {
    console.error(e);
    process.exit(1);
});
