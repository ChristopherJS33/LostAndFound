package com.lostandfound;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class MatchRunner {
    public static void main(String[] args) throws Exception {
        StringBuilder input = new StringBuilder();
        try (BufferedReader reader = new BufferedReader(new InputStreamReader(System.in))) {
            String line;
            while ((line = reader.readLine()) != null) {
                input.append(line);
            }
        }

        String json = input.toString();
        Item source = parseSource(json);
        List<Item> candidates = parseCandidates(json);

        MatchService service = new MatchService(new SimpleMatcher());
        List<MatchResult> matches = service.findMatches(source, candidates);

        StringBuilder out = new StringBuilder("[");
        for (int i = 0; i < matches.size(); i++) {
            MatchResult result = matches.get(i);
            if (i > 0) out.append(',');
            out.append('{')
               .append("\"id\":").append(result.getId()).append(',')
               .append("\"score\":").append(String.format(java.util.Locale.US, "%.4f", result.getScore()))
               .append('}');
        }
        out.append(']');
        System.out.print(out.toString());
    }

    private static Item parseSource(String json) {
        String sourceBlock = extractObject(json, "source");
        return buildItem(sourceBlock);
    }

    private static List<Item> parseCandidates(String json) {
        String candidatesBlock = extractArray(json, "candidates");
        List<Item> items = new ArrayList<>();
        Pattern objectPattern = Pattern.compile("\\{(.*?)\\}");
        Matcher matcher = objectPattern.matcher(candidatesBlock);
        while (matcher.find()) {
            String objectBody = "{" + matcher.group(1) + "}";
            items.add(buildItem(objectBody));
        }
        return items;
    }

    private static Item buildItem(String jsonObject) {
        int id = parseInt(jsonObject, "id");
        String title = parseString(jsonObject, "title");
        String description = parseString(jsonObject, "description");
        String category = parseString(jsonObject, "category");
        String location = parseString(jsonObject, "location");
        String status = parseString(jsonObject, "status");

        if ("found".equalsIgnoreCase(status)) {
            return new FoundItem(id, title, description, category, location);
        }
        return new LostItem(id, title, description, category, location);
    }

    private static String extractObject(String json, String key) {
        Pattern pattern = Pattern.compile("\\\"" + key + "\\\"\\s*:\\s*(\\{.*?\\})(,|$)");
        Matcher matcher = pattern.matcher(json);
        if (matcher.find()) return matcher.group(1);
        return "{}";
    }

    private static String extractArray(String json, String key) {
        Pattern pattern = Pattern.compile("\\\"" + key + "\\\"\\s*:\\s*(\\[.*\\])");
        Matcher matcher = pattern.matcher(json);
        if (matcher.find()) return matcher.group(1);
        return "[]";
    }

    private static String parseString(String jsonObject, String key) {
        Pattern pattern = Pattern.compile("\\\"" + key + "\\\"\\s*:\\s*(null|\\\"(.*?)\\\")");
        Matcher matcher = pattern.matcher(jsonObject);
        if (matcher.find()) {
            if ("null".equals(matcher.group(1))) return "";
            return unescape(matcher.group(2));
        }
        return "";
    }

    private static int parseInt(String jsonObject, String key) {
        Pattern pattern = Pattern.compile("\\\"" + key + "\\\"\\s*:\\s*(\\d+)");
        Matcher matcher = pattern.matcher(jsonObject);
        if (matcher.find()) {
            return Integer.parseInt(matcher.group(1));
        }
        return 0;
    }

    private static String unescape(String value) {
        return value.replace("\\\\", "\\")
                    .replace("\\\"", "\"")
                    .replace("\\n", " ")
                    .replace("\\r", " ")
                    .replace("\\t", " ");
    }
}
