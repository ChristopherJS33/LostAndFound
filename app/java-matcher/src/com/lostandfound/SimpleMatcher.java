package com.lostandfound;

import java.util.Arrays;
import java.util.HashSet;
import java.util.Set;

public class SimpleMatcher implements Matcher {
    @Override
    public double calculateMatchScore(Item source, Item candidate) {
        double score = 0.0;

        if (source.getCategory().equalsIgnoreCase(candidate.getCategory())) {
            score += 0.35;
        }

        if (source.getLocation().equalsIgnoreCase(candidate.getLocation())) {
            score += 0.25;
        }

        score += titleSimilarity(source.getTitle(), candidate.getTitle()) * 0.20;
        score += descriptionSimilarity(source.getDescription(), candidate.getDescription()) * 0.20;

        return Math.min(score, 1.0);
    }

    private double titleSimilarity(String a, String b) {
        if (a == null || b == null || a.isBlank() || b.isBlank()) return 0.0;
        return tokenOverlap(a, b);
    }

    private double descriptionSimilarity(String a, String b) {
        if (a == null || b == null || a.isBlank() || b.isBlank()) return 0.0;
        return tokenOverlap(a, b);
    }

    private double tokenOverlap(String a, String b) {
        Set<String> setA = tokenize(a);
        Set<String> setB = tokenize(b);
        if (setA.isEmpty() || setB.isEmpty()) return 0.0;

        int common = 0;
        for (String token : setA) {
            if (setB.contains(token)) common++;
        }
        return (double) common / (double) Math.max(setA.size(), setB.size());
    }

    private Set<String> tokenize(String text) {
        String cleaned = text.toLowerCase().replaceAll("[^a-z0-9 ]", " ").trim();
        if (cleaned.isBlank()) return new HashSet<>();
        return new HashSet<>(Arrays.asList(cleaned.split("\\s+")));
    }
}
